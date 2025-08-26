<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\User;
use DateTime;
use Tests\Feature\Helper\DebitCardTestHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        DebitCardTestHelper::createDebitCard($this);
        $url = '/api/debit-cards';
        $response = $this->get($url);
        $response->assertStatus(200);
        
        // assert that response not empty
        $this->assertNotEmpty($response->json());

        // assert that response have correct structure
        $response->assertJsonStructure([
            '*' => [
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ]
        ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // create other user
        $secondUser = User::factory()->create();

        // create debit card for first user
        DebitCardTestHelper::createDebitCard($this);

        $url = '/api/debit-cards';
        $response = $this->actingAs($secondUser)->get($url);
        $response->assertStatus(200);
        
        // assert that response empty
        $this->assertEmpty($response->json());
    }

    public function testCustomerCanCreateADebitCard()
    {
        $response = DebitCardTestHelper::createDebitCard($this);
        $response->assertStatus(201)->assertJsonStructure(['id', 'type', 'number', 'expiration_date','is_active']);

        $jsonResponse = $response->json();

        // assert that database has created debit card data
        $date = new DateTime($jsonResponse['expiration_date']);
        $expirationDate = $date->format('Y-m-d H:i:s');
        $this->assertDatabaseHas('debit_cards', [
            'id' => $jsonResponse['id'],
            'user_id' => $this->user->id,
            'type' => $jsonResponse['type'],
            'expiration_date' => $expirationDate,
        ]);

        // assert that expiration date is in the future
        $isFuture = $expirationDate >= now()->toDateTimeString();
        $this->assertTrue($isFuture);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $debitCard = DebitCardTestHelper::createDebitCard($this);
        
        $url = 'api/debit-cards/'.$debitCard->json()['id'];
        $response = $this->get($url);
        $response->assertStatus(200);

        // assert that response have correct structure
        $response->assertJsonStructure([
            'id',
            'number',
            'type',
            'expiration_date',
            'is_active',
        ]);
        
        // assert that response have correct data
        $date = new DateTime($debitCard->json()['expiration_date']);
        $expirationDate = $date->format('Y-m-d H:i:s');
        $this->assertEquals($debitCard->json()['id'], $response->json()['id']);
        $this->assertEquals($debitCard->json()['type'], $response->json()['type']);
        $this->assertEquals($expirationDate, $response->json()['expiration_date']);
        $this->assertEquals($debitCard->json()['is_active'], $response->json()['is_active']);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // create other user
        $secondUser = User::factory()->create();

        // create debit card for first user
        $debitCard = DebitCardTestHelper::createDebitCard($this);

        $url = '/api/debit-cards/'.$debitCard->json()['id'];
        $response = $this->actingAs($secondUser)->get($url);
        
        // assert status 403 to make sure second user cannot access debit card detail
        $response->assertStatus(403);
    }

    public function testCustomerCanActivateADebitCard()
    {
        $debitCard = DebitCardTestHelper::createDebitCard($this);
        $url = '/api/debit-cards/'.$debitCard->json()['id'];

        $data = [
            'is_active' => true,
        ];
        $response = $this->put($url, $data);
        $response->assertStatus(200);

        $response->assertJsonStructure([
            'id',
            'number',
            'type',
            'expiration_date',
            'is_active',
        ]);

        // assert that debit card is active
        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->json()['id'],
            'disabled_at' => null,
        ]);

        // assert that response have correct data
        $this->assertEquals($debitCard->json()['id'], $response->json()['id']);
        $this->assertEquals($debitCard->json()['type'], $response->json()['type']);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        $debitCard = DebitCardTestHelper::createDebitCard($this);
        $url = '/api/debit-cards/'.$debitCard->json()['id'];

        $data = [
            'is_active' => false,
        ];
        $response = $this->put($url, $data);
        $response->assertStatus(200);

        $response->assertJsonStructure([
            'id',
            'number',
            'type',
            'expiration_date',
            'is_active',
        ]);
        
        // assert that debit card is deactivated
        $this->assertEquals($response->json()['is_active'], false);

        // assert that response have correct data
        $this->assertEquals($debitCard->json()['id'], $response->json()['id']);
        $this->assertEquals($debitCard->json()['type'], $response->json()['type']);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // create other user
        $secondUser = User::factory()->create();

        $debitCard = DebitCardTestHelper::createDebitCard($this);
        $url = '/api/debit-cards/'.$debitCard->json()['id'];

        $data = [
            'is_active' => false,
        ];
        $response = $this->actingAs($secondUser)->put($url, $data);
        $response->assertStatus(403);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        $debitCard = DebitCardTestHelper::createDebitCard($this);
        $url = '/api/debit-cards/'.$debitCard->json()['id'];

        $response = $this->delete($url);
        $response->assertStatus(204);

        // assert that debit card is deleted
        $this->assertSoftDeleted('debit_cards', [
            'id' => strval($debitCard->json()['id']),
        ]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        $debitCard = DebitCardTestHelper::createDebitCard($this);
        $url = '/api/debit-cards/'.$debitCard->json()['id'];

        $responseCreateTransaction = DebitCardTestHelper::createTransaction($this, $debitCard->json()['id']);
        $responseCreateTransaction->assertStatus(201);

        $responseDelete = $this->delete($url);
        
        $responseDelete->assertStatus(403);
    }

    // Extra bonus for extra tests :)

    public function testCustomerCannotDeleteADebitCardOfOtherCustomer()
    {
        $secondUser = User::factory()->create();

        $debitCard = DebitCardTestHelper::createDebitCard($this);
        $url = '/api/debit-cards/'.$debitCard->json()['id'];

        $response = $this->actingAs($secondUser)->delete($url);

        $response->assertStatus(403);
    }

    public function testCustomerCannotUpdateADebitCardOfOtherCustomer()
    {
        $secondUser = User::factory()->create();

        $debitCard = DebitCardTestHelper::createDebitCard($this);

        $url = '/api/debit-cards/'.$debitCard->json()['id'];

        $data = [
            'is_active' => false,
        ];
        $response = $this->actingAs($secondUser)->put($url, $data);
        $response->assertStatus(403);
    }

    public function testCustomerCannotSeeDebitCardThatNotExists()
    {
        $url = '/api/debit-cards/4'; //use random id

        $response = $this->get($url);   
        $response->assertStatus(404);
    }
}
