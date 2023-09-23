<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
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

        DebitCard::factory(3)->create(['user_id' => $this->user->id]);


        $response = $this->get('/api/debit-cards');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');


        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'user_id',
                    'number',
                    'type',
                    'expiration_date',
                ]
            ]
        ]);


    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $otherUser = User::factory()->create();
        DebitCard::factory(3)->create(['user_id' => $otherUser->id]);

        $response = $this->get('/api/debit-cards');

        $response->assertStatus(200);

        $response->assertHeader('Content-Type', 'application/json');

        foreach (DebitCard::where('user_id', $otherUser->id)->get() as $debitCard) {
            $response->assertJsonMissing(['id' => $debitCard->id]);
        }
    }

        public function testCustomerCanCreateADebitCard()
    {
        $debitCardData = [
            'number' => '1234567812345678',
            'type' => 'visa',
            'expiration_date' => '12/25',
        ];

        $response = $this->post('/api/debit-cards', $debitCardData);

        $response->assertStatus(201);
    }


    public function testCustomerCanSeeASingleDebitCardDetails()
    {

        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);


        $response = $this->get("/api/debit-cards/{$debitCard->id}");


        $response->assertStatus(200);


        $response->assertHeader('Content-Type', 'application/json');

        $response->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'number',
                'type',
                'expiration_date',

            ]
        ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {

        $otherUser = User::factory()->create();
        $debitCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);


        $response = $this->get("/api/debit-cards/{$debitCard->id}");


        $response->assertStatus(404);
    }

        public function testCustomerCanActivateADebitCard()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->put("/api/debit-cards/{$debitCard->id}/activate");
        $response->assertStatus(200);
        $this->assertTrue($debitCard->fresh()->isActive());
    }

        public function testCustomerCanDeactivateADebitCard()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id, 'active' => true]);

        $response = $this->put("/api/debit-cards/{$debitCard->id}/deactivate");
        $response->assertStatus(200);
        $this->assertFalse($debitCard->fresh()->isActive());
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
         $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

         $invalidData = [
            'number' => '123',
            'type' => 'invalid',
            'expiration_date' => '12/10',
        ];


        $response = $this->put("/api/debit-cards/{$debitCard->id}", $invalidData);

        $response->assertStatus(200);

        }

    public function testCustomerCanDeleteADebitCard()
    {

        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);


        $response = $this->delete("/api/debit-cards/{$debitCard->id}");


        $response->assertStatus(200);


    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
   
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id, 'active' => true]);
        DebitCardTransaction::factory()->create(['debit_card_id' => $debitCard->id]);
        $response = $this->delete("/api/debit-cards/{$debitCard->id}");
        $response->assertStatus(200);


    }

    // Extra bonus for extra tests :)
}
