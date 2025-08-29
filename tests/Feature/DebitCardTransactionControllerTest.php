<?php

namespace Tests\Feature;

use App\Models\DebitCard;
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

        // create transactions
        $this->debitCard->debitCardTransactions()->create([
            'amount' => 100000,
            'currency_code' => 'IDR',
        ]);

        $response = $this->getJson("/api/debit-card-transactions?debit_card_id={$this->debitCard->id}");

        $response->assertStatus(200);

    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
        
        $otherCustomer = User::factory()->create();
        $otherCustomerDebitCard = DebitCard::factory()->create([
            'user_id' => $otherCustomer->id,
        ]);

        $otherCustomerDebitCard->debitCardTransactions()->create([
            'amount' => 100000,
            'currency_code' => 'IDR',
        ]);

        $response = $this->getJson("/api/debit-card-transactions?debit_card_id={$otherCustomerDebitCard->id}");

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.'
        ]);
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
        $response = $this->postJson("api/debit-card-transactions?debit_card_id={$this->debitCard->id}", [
            'amount' => 200000,
            'currency_code' => 'IDR',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'amount' => 200000,
            'currency_code' => 'IDR',
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions

        $otherCustomer = User::factory()->create();
        $otherCustomerDebitCard = DebitCard::factory()->create([
            'user_id' => $otherCustomer->id,
        ]);

        $response = $this->postJson("api/debit-card-transactions?debit_card_id={$otherCustomerDebitCard->id}", [
            'amount' => 220000,
            'currency_code' => 'IDR'
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.'
        ]);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}

        $debitCardTransaction = $this->debitCard->debitCardTransactions()->create([
            'amount' => 250000,
            'currency_code' => 'IDR'
        ]);

        $response = $this->getJson("api/debit-card-transactions/{$debitCardTransaction->id}");

        // $response->dump();
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'amount' => "250000",
            'currency_code' => 'IDR'
        ]);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $otherCustomer = User::factory()->create();
        
        $otherCustomerDebitCard = DebitCard::factory()->create([
            'user_id' => $otherCustomer->id,
        ]);
        
        $otherCustomerDebitCardTransaction = $otherCustomerDebitCard->debitCardTransactions()->create([
            'amount' => 150000,
            'currency_code' => 'IDR',
        ]);

        $response = $this->getJson("api/debit-card-transactions/{$otherCustomerDebitCardTransaction->id}");

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.'
        ]);
    }

    // Extra bonus for extra tests :)
}
