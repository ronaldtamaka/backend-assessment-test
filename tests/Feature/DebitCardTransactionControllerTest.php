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
        $this->debitCard->debitCardTransactions()->createMany(
            DebitCardTransaction::factory()->count(3)->make()->toArray()
        );

        $this->getJson('/api/debit-card-transactions?debit_card_id=' . $this->debitCard->id)
            ->assertOk()
            ->assertJsonCount(3)
            ->assertJsonStructure([
                '*' => [
                    'amount',
                    'currency_code',
                ]
            ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
        $anotherUser = User::factory()->create();
        $anotherDebitCard = DebitCard::factory()->create([
            'user_id' => $anotherUser->id
        ]);
        $anotherDebitCard->debitCardTransactions()->createMany(
            DebitCardTransaction::factory()->count(3)->make()->toArray()
        );

        $this->getJson('/api/debit-card-transactions?debit_card_id=' . $anotherDebitCard->id)
            ->assertForbidden();
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
        $payload = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 1000,
            'currency_code' => DebitCardTransaction::CURRENCY_IDR,
        ];

        $response = $this->postJson('/api/debit-card-transactions', $payload);

        $response->assertCreated()
                ->assertJsonStructure([
                    'amount',
                    'currency_code',
                ]);

        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => $payload['amount'],
            'currency_code' => $payload['currency_code'],
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
        $anotherUser = User::factory()->create();
        $anotherDebitCard = DebitCard::factory()->create([
            'user_id' => $anotherUser->id
        ]);

        $payload = [
            'debit_card_id' => $anotherDebitCard->id,
            'amount' => 1000,
            'currency_code' => 'USD',
        ];

        $this->postJson('/api/debit-card-transactions', $payload)
            ->assertForbidden();
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id,
        ]);

        $response = $this->getJson("/api/debit-card-transactions/{$transaction->id}");

        $response->assertOk();
        $response->assertJsonFragment([
            'amount' => (string) $transaction->amount,
            'currency_code' => $transaction->currency_code,
        ]);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
    }

    // Extra bonus for extra tests :)
}
