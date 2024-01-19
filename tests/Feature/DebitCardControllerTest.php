<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
// use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DebitCardControllerTest extends TestCase
{
    // use DatabaseTransactions;
    use RefreshDatabase, WithFaker;
    private string $baseUrl = 'api/debit-cards';

    protected User $user;
    protected User $userOther;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
        $this->userOther = User::factory()->create();


        DebitCard::factory(10)
            ->create(["user_id" => $this->user->id]);
    }

    protected function createUser(): User
    {
        return User::factory()->create();
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        // get /debit-cards
      
        $debitCardsCount = $this->faker->numberBetween(1, 10);
        DebitCard::factory()->for($this->user)->count($debitCardsCount)->active()->create();
        DebitCard::factory()->for($this->user)->count($debitCardsCount)->expired()->create();
        $response = $this->getJson($this->baseUrl);
        $response->assertOk()
            ->assertJsonStructure([
                [
                    'id', 'number', 'expiration_date', 'is_active'
                ]
            ]);

        $response->assertJsonCount($debitCardsCount);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        $debitCardsCount = $this->faker->numberBetween(1, 10);
        $debitCards = DebitCard::factory()->for($this->user)->count($debitCardsCount)->active()->create();
        DebitCard::factory()->for(User::factory())->count($debitCardsCount)->active()->create();
        $response = $this->getJson($this->baseUrl);
        $response->assertOk()
            ->assertJsonStructure([
                [
                    'id', 'number', 'expiration_date', 'is_active'
                ]
            ]);

        foreach ($response->json() as $debitCard) {
            $this->assertContains($debitCard['id'], $debitCards->pluck('id'));
        }
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $params = ['type' => ''];
        $response = $this->post('/api/debit-cards', $params);
        $response->assertStatus(HttpResponse::HTTP_FOUND);
        $this->assertDatabaseMissing('debit_cards', ['type' => 'Visa']);

        $params = ['type' => 'Visa'];
        $response = $this->post('/api/debit-cards', $params);
        $response->assertStatus(HttpResponse::HTTP_CREATED)
         ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active'
            ]);
        $this->assertDatabaseHas('debit_cards', ['type' => 'Visa']);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->for($this->user)->create();
        $response = $this->getJson($this->baseUrl . '/' . $debitCard->id);
        $response->assertOk()
            ->assertJsonStructure([
                'id','number','expiration_date','is_active'
            ]);

        $response->assertJson([
            'id' => $debitCard->id,
            'number' => $debitCard->number,
            'is_active' => $debitCard->is_active,
            'expiration_date' => $debitCard->expiration_date->format('Y-m-d H:i:s'),
        ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->for(User::factory())->create();
        $response = $this->getJson($this->baseUrl . '/' . $debitCard->id);
        $response->assertForbidden();
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->for($this->user)->expired()->create();
        $response = $this->putJson($this->baseUrl . '/' . $debitCard->id,[
            'is_active' => true,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'id','number','expiration_date','is_active'
            ]);

        $response->assertJson([
            'id' => $debitCard->id,
            'number' => $debitCard->number,
            'is_active' => true,
            'expiration_date' => $debitCard->expiration_date->format('Y-m-d H:i:s'),
        ]);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'disabled_at' => null,
        ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = $this->user->debitCards->first();
        $params = ['is_active' => ''];
        $response = $this->put('api/debit-cards/'. $debitCard->id, $params);
        $response->assertStatus(HttpResponse::HTTP_FOUND);
        $this->assertDatabaseMissing('debit_cards', ['disabled_at' => '']);

        $params = ['is_active' => false];
        $response = $this->put('api/debit-cards/'. $debitCard->id, $params);
        $response->assertStatus(HttpResponse::HTTP_OK)
        ->assertJsonStructure([
            'id',
            'number',
            'type',
            'expiration_date',
            'is_active'
        ]);
        $this->assertDatabaseMissing('debit_cards', ['id' => $debitCard->id, 'disabled_at' => null]);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = $this->user->debitCards->first();
        $params = ['is_active' => '98765432'];
        $response = $this->put('api/debit-cards/'. $debitCard->id, $params);
        $response->assertStatus(HttpResponse::HTTP_FOUND);
        $this->assertDatabaseMissing('debit_cards', ['id'=> $debitCard->id, 'disabled_at' => '98765432']);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = $this->user->debitCards->first();
        $response = $this->delete('api/debit-cards/' . $debitCard->id);
        $response->assertStatus(HttpResponse::HTTP_NO_CONTENT);
        $this->assertDatabaseMissing('debit_cards', ['id' => $debitCard->id, 'delete_at' => null]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = $this->user->debitCards->first();
        DebitCardTransaction::factory()->create(["debit_card_id" => $debitCard->id]);
        $response = $this->delete('api/debit-cards/'. $debitCard->id);
        $response->assertStatus(HttpResponse::HTTP_FORBIDDEN);
        $this->assertDatabaseHas('debit_cards', ['id' => $debitCard->id]);
    }

    // Extra bonus for extra tests :)
}
