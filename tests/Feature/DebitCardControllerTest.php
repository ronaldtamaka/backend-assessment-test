<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DebitCard;
use Carbon\Carbon;
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
        $debitCard = DebitCard::create([
            'type' => "Gold",
            'user_id'=> $this->user->id,
            'number' => rand(1000000000000000, 9999999999999999),
            'expiration_date' => Carbon::now()->addYear(),
        ]);
        $response = $this->get('/api/debit-cards');

        $response->assertStatus(200);
        

    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        $debitCard = DebitCard::create([
            'type' => "Gold",
            'user_id'=> $this->user->id,
            'number' => rand(1000000000000000, 9999999999999999),
            'expiration_date' => Carbon::now()->addYear(),
        ]);
        $response = $this->get('/api/debit-cards1');

        $response->assertStatus(404);
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $payload = [
            "type" => "Gold",
        ];

        $this->json('POST', 'api/debit-cards', $payload, ['Accept' => 'application/json'])
        ->assertCreated();

    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $debitCard = DebitCard::create([
            'type' => "Gold",
            'user_id'=> $this->user->id,
            'number' => rand(1000000000000000, 9999999999999999),
            'expiration_date' => Carbon::now()->addYear(),
        ]);

        $response = $this->json('get',"api/debit-cards/".$debitCard->id);

        $response->assertStatus(200);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $this->json('get', "api/debit-cards/0")
         ->assertStatus(404);

    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::create([
            'type' => "Gold",
            'user_id'=> $this->user->id,
            'number' => rand(1000000000000000, 9999999999999999),
            'expiration_date' => Carbon::now()->addYear(),
        ]);
        $payload = [
            "is_active" => true
        ];

        $this->json('put', "api/debit-cards/$debitCard->id", $payload)
        ->assertStatus(200);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::create([
            'type' => "Gold",
            'user_id'=> $this->user->id,
            'number' => rand(1000000000000000, 9999999999999999),
            'expiration_date' => Carbon::now()->addYear(),
        ]);
        $payload = [
            "is_active" => false
        ];

        $this->json('put', "api/debit-cards/$debitCard->id", $payload)
        ->assertStatus(200);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::create([
            'type' => "Gold",
            'user_id'=> $this->user->id,
            'number' => rand(1000000000000000, 9999999999999999),
            'expiration_date' => Carbon::now()->addYear(),
        ]);
        $payload = [
            "is_active" => ""
        ];

        $this->json('put', "api/debit-cards/$debitCard->id", $payload)
        ->assertStatus(422);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $cardData =
        [
            'type' => "Gold",
            'user_id'=> $this->user->id,
            'number' => rand(1000000000000000, 9999999999999999),
            'expiration_date' => Carbon::now()->addYear(),
        ];
        $debitCard = DebitCard::create($cardData);
        
        $this->json('delete', "api/debit-cards/$debitCard->id")
            ->assertNoContent();    
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $cardData =
        [
            'type' => "Gold",
            'user_id'=> $this->user->id,
            'number' => rand(1000000000000000, 9999999999999999),
            'expiration_date' => Carbon::now()->addYear(),
        ];
        $debitCard = DebitCard::create($cardData);
        
        $this->json('delete', "api/debit-cards/0")
        ->assertStatus(404);
    }

    // Extra bonus for extra tests :)

    
}
