<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use App\Models\DebitCardTransaction;
use Tests\TestCase;

class DebitCardTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user, $newUser;
    protected DebitCard $debitCard, $newUserDebitCard;
    protected DebitCardTransaction $currentUserDebitCardTransaction, $newUserDebitCardTransaction;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);

        $this->newUser = User::factory()->create();
        $this->newUserDebitCard = DebitCard::factory()->for($this->newUser)->create();

        $this->currentUserDebitCardTransaction = DebitCardTransaction::factory()
            ->for($this->debitCard)
            ->create();

        $this->newUserDebitCardTransaction = DebitCardTransaction::factory()
            ->for($this->newUserDebitCard)
            ->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCardTransactions()
    {
        DebitCardTransaction::factory()
            ->count(5) 
            ->for($this->debitCard)
            ->create();
        $response = $this->get('/api/debit-card-transactions?debit_card_id='.$this->debitCard->id);
        $response->assertStatus(200)->assertOk(); 
    }


    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherUserDebitCard = DebitCard::factory()->for($otherUser)->create();
        DebitCardTransaction::factory()
            ->count(3) 
            ->for($otherUserDebitCard)
            ->create();
            $response = $this->get('/api/debit-card-transactions?debit_card_id=' . $otherUserDebitCard->id);

            $response->assertForbidden();
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $transactionData = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 500,
            'currency_code' => 'IDR',
        ];

        $response = $this->post('/api/debit-card-transactions', $transactionData);
        $response->assertStatus(201);

        $response->assertJsonStructure([
            'amount',
            'currency_code'
        ]);

        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => $transactionData['amount'],
            'currency_code' => $transactionData['currency_code']
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        DebitCard::factory()->create();
        $transactionData = [
            'debit_card_id' => $this->newUserDebitCard->id,
            'amount' => 500,
            'currency_code' => 'IDR',
        ];
        $this->post('/api/debit-card-transactions', $transactionData)
            ->assertForbidden();

        $this->assertDatabaseMissing('debit_card_transactions', $transactionData);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        $debitCardTransaction = DebitCardTransaction::factory()->for($this->debitCard)->create();

        $response = $this->get('/api/debit-card-transactions/' . $debitCardTransaction->id);
        $response->assertJsonStructure([
            'amount',
            'currency_code',
        ]);
        $response->assertOk();
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        $response = $this->get('/api/debit-card-transactions/' . $this->newUserDebitCardTransaction->id);
        $response->assertForbidden();
    }

}
