<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
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
        DebitCardTransaction::factory()->count(2)->create([
            'debit_card_id' => $this->debitCard->id
        ]);
        $otherDebitCard = DebitCard::factory()->create();
        DebitCardTransaction::factory()->count(3)->create([
            'debit_card_id' => $otherDebitCard->id
        ]);

        $this->getJson('/api/debit-card-transactions?debit_card_id=' . $this->debitCard->id)
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'amount',
                    'currency_code'
                ]
            ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);
        DebitCardTransaction::factory()->count(3)->create([
            'debit_card_id' => $otherDebitCard->id
        ]);

        $this->getJson('/api/debit-card-transactions?debit_card_id=' . $otherDebitCard->id)
            ->assertForbidden();
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
        $payload = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 10000,
            'currency_code' => Arr::random(DebitCardTransaction::CURRENCIES)
        ];

        $this->postJson('/api/debit-card-transactions', $payload)
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'amount',
                'currency_code'
            ])
            ->assertJsonPath('amount', $payload['amount'])
            ->assertJsonPath('currency_code', $payload['currency_code']);

        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => $payload['amount'],
            'currency_code' => $payload['currency_code']
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $payload = [
            'debit_card_id' => $otherDebitCard->id,
            'amount' => 10000,
            'currency_code' => Arr::random(DebitCardTransaction::CURRENCIES)
        ];

        $this->postJson('/api/debit-card-transactions', $payload)
            ->assertForbidden();
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id
        ]);

        $this->getJson('/api/debit-card-transactions/' . $transaction->id)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'amount',
                'currency_code'
            ])
            ->assertJsonPath('id', $transaction->id);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);
        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $otherDebitCard->id
        ]);

        $this->getJson('/api/debit-card-transactions/' . $transaction->id)
            ->assertForbidden();
    }

    // Extra bonus for extra tests :)
}
