<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
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
        // get /debit-cards
        $debitCard = DebitCard::create([
            'type' => "Platinum",
            'user_id' => $this->user->id,
            'number' => rand(100000000, 999999999),
            'expiration_date' => Carbon::now()->addYear(4),
        ]);
        $res = $this->get('/api/debit-cards');

        $res->assertStatus(200);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        $newUser = User::factory()->create();
        $debitCard = DebitCard::create([
            'type' => "Platinum",
            'user_id' => $newUser->id,
            'number' => rand(100000000, 999999999),
            'expiration_date' => Carbon::now()->addYear(4),
        ]);
        $res = $this->get('/api/debit-cards');

        $res->assertStatus(200);
        $res->assertJsonCount(0);
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $cardData = [
            "type" => "Platinum",
        ];

        $res = $this->json(
            'POST',
            'api/debit-cards',
            $cardData,
            ['Accept' => 'application/json']
        );

        $resData = $res->json();
        $res->assertJsonStructure([
            'id',
            'number',
            'type',
            'expiration_date',
            'is_active',
        ]);

        $res->assertSuccessful();
        $this->assertDatabaseHas(
            'debit_cards',
            [
                'user_id' => $this->user->id,
                'type' => 'Platinum',
            ]
        );
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $debitCard = DebitCard::create([
            'type' => "Platinum",
            'user_id' => $this->user->id,
            'number' => rand(100000000, 999999999),
            'expiration_date' => Carbon::now()->addYear(4),
        ]);

        $response = $this->json('get', "api/debit-cards/" . $debitCard->id);

        $response->assertStatus(200);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $newUser = User::factory()->create();
        $debitCard = DebitCard::create([
            'type' => "Platinum",
            'user_id' => $newUser->id,
            'number' => rand(100000000, 999999999),
            'expiration_date' => Carbon::now()->addYear(4),
        ]);

        $response = $this->json('get', "api/debit-cards/" . $debitCard->id);

        $response->assertStatus(403);
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::create([
            'type' => "Platinum",
            'user_id' => $this->user->id,
            'number' => rand(100000000, 999999999),
            'expiration_date' => Carbon::now()->addYear(4),
        ]);

        $statusCard = [
            "is_active" => true
        ];

        $res = $this->json('put', "api/debit-cards/$debitCard->id", $statusCard);
        $res->assertStatus(200);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::create([
            'type' => "Platinum",
            'user_id' => $this->user->id,
            'number' => rand(100000000, 999999999),
            'expiration_date' => Carbon::now()->addYear(4),
        ]);

        $statusCard = [
            "is_active" => false
        ];

        $res = $this->json('put', "api/debit-cards/$debitCard->id", $statusCard);
        $res->assertStatus(200);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::create([
            'type' => "Platinum",
            'user_id' => $this->user->id,
            'number' => rand(100000000, 999999999),
            'expiration_date' => Carbon::now()->addYear(4),
        ]);

        $statusCard = [
            "is_active" => ""
        ];

        $res = $this->json('put', "api/debit-cards/$debitCard->id", $statusCard);
        $res->assertStatus(422);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = DebitCard::create([
            'type' => "Platinum",
            'user_id' => $this->user->id,
            'number' => rand(100000000, 999999999),
            'expiration_date' => Carbon::now()->addYear(4),
        ]);

        $res = $this->json('delete', "api/debit-cards/$debitCard->id");
        $res->assertStatus(204);
        $this->assertSoftDeleted('debit_cards', ['id' => $debitCard->id]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = DebitCard::create([
            'type' => "Platinum",
            'user_id' => $this->user->id,
            'number' => rand(100000000, 999999999),
            'expiration_date' => Carbon::now()->addYear(4),
        ]);
        $transactionDebitCard = DebitCardTransaction::create([
            'debit_card_id' => $debitCard->id,
            'amount' => rand(100000000, 999999999),
            'currency_code' => DebitCardTransaction::CURRENCY_IDR,
        ]);

        $res = $this->json('delete', "api/debit-cards/$debitCard->id");
        $res->assertForbidden();
    }

    // Extra bonus for extra tests :)
}
