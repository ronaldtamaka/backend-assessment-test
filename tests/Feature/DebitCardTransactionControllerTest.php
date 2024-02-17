<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected DebitCard $debitCard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCardTransactions()
    {
        // get /debit-card-transactions
        $id = $this->debitCard->id;
        DebitCardTransaction::factory(5)->create([
            'debit_card_id' => $id,
        ]);

        $response = $this->get("/api/debit-card-transactions?debit_card_id=$id");

        $response->assertStatus(200)
            ->assertJsonStructure([
                [
                    'amount',
                    'currency_code',
                ]
            ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
        $id = $this->debitCard->id;
        DebitCardTransaction::factory(5)->create([
            'debit_card_id' => $id,
        ]);

        $anotherUser = User::factory()->create();
        Passport::actingAs($anotherUser);

        $response = $this->get("/api/debit-card-transactions?debit_card_id=$id");

        $response->assertStatus(403)->assertForbidden();
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
        $data = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 10000000,
            'currency_code' => 'IDR'
        ];

        $response = $this->post('/api/debit-card-transactions', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'amount',
                'currency_code',
            ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
        $anotherUser = User::factory()->create();
        $anotherDebitCard = DebitCard::factory()->create([
            'user_id' => $anotherUser->id
        ]);

        $data = [
            'debit_card_id' => $anotherDebitCard->id,
            'amount' => 10000000,
            'currency_code' => 'IDR'
        ];

        $response = $this->actingAs($this->user)->postJson('/api/debit-card-transactions', $data);
        $response->assertStatus(403)->assertForbidden();
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $debitCardTransaction = $this->debitCard->debitCardTransactions()->create([
            'amount' => 10000000,
            'currency_code' => 'IDR'
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/debit-card-transactions/{$debitCardTransaction->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'amount',
                'currency_code',
            ]);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $anotherUser = User::factory()->create();
        $anotherDebitCard = DebitCard::factory()->create([
            'user_id' => $anotherUser->id
        ]);

        $anotherDebitCardTransaction = $anotherDebitCard->debitCardTransactions()->create([
            'amount' => 10000000,
            'currency_code' => 'IDR'
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/debit-card-transactions/{$anotherDebitCardTransaction->id}");
        $response->assertStatus(403)->assertForbidden();
    }

    // Extra bonus for extra tests :)

    public function testCustomerCannotCreateADebitCardTransactionWrongValidation()
    {
        // get /debit-card-transactions
        $data = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 2000,
            'currency_code' => 'USD'
        ];

        $response = $this->post('/api/debit-card-transactions', $data);
        $response->assertStatus(302);
    }

}
