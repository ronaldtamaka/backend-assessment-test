<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\User;
use App\Models\DebitCardTransaction;
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
        DebitCardTransaction::factory()->count(2)->create([
            'debit_card_id' => $this->debitCard->id,
        ]);

        $this->getJson('/api/debit-card-transactions?debit_card_id=' . $this->debitCard->id)
            ->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);
        DebitCardTransaction::factory()->create(['debit_card_id' => $otherCard->id]);

        $this->getJson('/api/debit-card-transactions?debit_card_id=' . $otherCard->id)
            ->assertStatus(403);
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $payload = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 1500,
            'currency_code' => 'IDR',
        ];

        $this->postJson('/api/debit-card-transactions', $payload)
            ->assertStatus(201)
            ->assertJsonPath('amount', 1500);

        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 1500,
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);

        $payload = [
            'debit_card_id' => $otherCard->id,
            'amount' => 500,
            'currency_code' => 'IDR',
        ];

        $this->postJson('/api/debit-card-transactions', $payload)
            ->assertStatus(403);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        $trx = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id,
            'amount' => 200,
            'currency_code' => 'IDR',
        ]);

        $this->getJson('/api/debit-card-transactions/' . $trx->id . '?debit_card_id=' . $this->debitCard->id)
            ->assertStatus(200)
            ->assertJsonPath('amount', 200)
            ->assertJsonPath('currency_code', 'IDR');
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);
        $trx = DebitCardTransaction::factory()->create(['debit_card_id' => $otherCard->id]);

        $this->getJson('/api/debit-card-transactions/' . $trx->id)
            ->assertStatus(403);
    }

    // Extra bonus for extra tests :)
}
