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
        $debitCardTransaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id
        ]);
        
        $response = $this->getJson('/api/debit-card-transactions?debit_card_id='.$this->debitCard->id);
        $response
            ->assertOk();
        
        $this->assertDatabaseHas('debit_card_transactions', [
            'id' => $debitCardTransaction->id,
            'amount' => $debitCardTransaction->amount,
            'currency_code' => $debitCardTransaction->currency_code,
        ]); 
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
        $debitCardTransaction = DebitCardTransaction::factory()->create();
        
        $response = $this->getJson('/api/debit-card-transactions?debit_card_id='.$this->debitCard->id);
        $response
            ->assertOk();

        $response->assertJsonMissing([
            'id' => $debitCardTransaction->id,
            'amount' => $debitCardTransaction->amount,
            'currency_code' => $debitCardTransaction->currency_code,
        ]);
        
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
        $response = $this->postJson('/api/debit-card-transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 1000,
            'currency_code' => DebitCardTransaction::CURRENCY_IDR,
        ]);
        $response->assertCreated();
        
        $this->assertDatabaseHas('debit_card_transactions', [
            'amount' => 1000,
            'currency_code' => DebitCardTransaction::CURRENCY_IDR,
        ]);
        
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
        $debitCard = DebitCard::factory()->create();

        $response = $this->postJson('/api/debit-card-transactions', [
            'debit_card_id' => $debitCard->id,
            'amount' => 1000,
            'currency_code' => DebitCardTransaction::CURRENCY_IDR,
        ]);
        $response
            ->assertForbidden();
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $debitCardTransaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id
        ]);
        
        $response = $this->getJson("/api/debit-card-transactions/{$debitCardTransaction->id}");
        $response
            ->assertOk();
        
        $this->assertDatabaseHas('debit_card_transactions', [
            'id' => $debitCardTransaction->id,
            'amount' => $debitCardTransaction->amount,
            'currency_code' => $debitCardTransaction->currency_code,
        ]);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $debitCardTransaction = DebitCardTransaction::factory()->create();

        $response = $this->get("/api/debit-card-transactions/{$debitCardTransaction->id}");

        $response->assertForbidden();
    }

    // Extra bonus for extra tests :)
}
