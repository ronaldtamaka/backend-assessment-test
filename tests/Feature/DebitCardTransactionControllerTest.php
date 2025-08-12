<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebitCardTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $debitCard;

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
        DebitCardTransaction::factory()->count(2)->create([
            'debit_card_id' => $this->debitCard->id,
        ]);

        $response = $this->getJson('/api/debit-card-transactions?debit_card_id=' . $this->debitCard->id);

        $response->assertOk();
        $response->assertJsonCount(2);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);
        DebitCardTransaction::factory()->create(['debit_card_id' => $otherCard->id]);

        $response = $this->getJson('/api/debit-card-transactions?debit_card_id=' . $otherCard->id);

        $response->assertForbidden();
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $payload = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 10000,
            'currency_code' => DebitCardTransaction::CURRENCY_IDR,
        ];

        $response = $this->postJson('/api/debit-card-transactions', $payload);

        $response->assertCreated();
        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 10000,
            'currency_code' => DebitCardTransaction::CURRENCY_IDR,
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);

        $payload = [
            'debit_card_id' => $otherCard->id,
            'amount' => 5000,
            'currency_code' => 'USD',
        ];

        $response = $this->postJson('/api/debit-card-transactions', $payload);

        $response->assertForbidden();
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id,
        ]);

        $response = $this->getJson("/api/debit-card-transactions/{$transaction->id}");

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $transaction->id,
            'debit_card_id' => $this->debitCard->id,
        ]);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);
        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $otherCard->id,
        ]);

        $response = $this->getJson("/api/debit-card-transactions/{$transaction->id}");

        $response->assertForbidden();
    }
}