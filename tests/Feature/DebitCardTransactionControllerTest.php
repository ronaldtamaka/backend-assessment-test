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
        DebitCardTransaction::factory()->count(3)->create([
            'debit_card_id' => $this->debitCard->id,
        ]);
        DebitCardTransaction::factory()->count(3)->create();

        $this->getJson("/api/debit-card-transactions?debit_card_id={$this->debitCard->id}")
            ->assertOk()
            ->assertJsonCount(3);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);

        DebitCardTransaction::factory()->count(3)->create([
            'debit_card_id' => $otherDebitCard->id,
        ]);

        $this->getJson("/api/debit-card-transactions?debit_card_id={$otherDebitCard->id}")
            ->assertForbidden();
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $payload = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 200,
            'currency_code' => 'SGD',
        ];

        $this->postJson('/api/debit-card-transactions', $payload)
            ->assertCreated()
            ->assertJsonStructure(['amount', 'currency_code'])
            ->assertJsonFragment([
                'amount' => $payload['amount'],
                'currency_code' => $payload['currency_code'],
            ]);

        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => $payload['amount'],
            'currency_code' => $payload['currency_code'],
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);

        $payload = [
            'debit_card_id' => $otherCard->id,
            'amount' => 500,
            'currency_code' => 'USD',
        ];

        $this->postJson('/api/debit-card-transactions', $payload)
            ->assertForbidden();

        $this->assertDatabaseMissing('debit_card_transactions', [
            'debit_card_id' => $otherCard->id,
            'amount' => $payload['amount'],
            'currency_code' => $payload['currency_code'],
        ]);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id,
            'amount' => 120,
            'currency_code' => 'SGD',
        ]);

        $this->getJson("/api/debit-card-transactions/{$transaction->id}")
            ->assertOk()
            ->assertJson([
                'amount' => 120,
                'currency_code' => 'SGD',
            ]);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);

        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $otherCard->id,
            'amount' => 999,
        ]);

        $this->getJson("/api/debit-card-transactions/{$transaction->id}")
            ->assertForbidden();
    }

    // Extra bonus for extra tests :)

    public function testCustomerCannotCreateTransactionWithInvalidData()
    {
        $payload = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => null,
            'currency_code' => '',
        ];

        $this->postJson('/api/debit-card-transactions', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['amount', 'currency_code']);
    }
}
