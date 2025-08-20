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
        $this->debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCardTransactions()
    {
        DebitCardTransaction::factory()
            ->count(2)
            ->for($this->debitCard)
            ->create();

        $response = $this->getJson('/api/debit-card-transactions?debit_card_id=' . $this->debitCard->id);

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        $otherCard = DebitCard::factory()->create();
        DebitCardTransaction::factory()->for($otherCard)->create();

        $response = $this->getJson('/api/debit-card-transactions?debit_card_id=' . $otherCard->id);

        $response->assertStatus(200)
                 ->assertJsonCount(0, 'data');
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $payload = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 100,
            'currency_code' => 'SGD'
        ];

        $response = $this->postJson('/api/debit-card-transactions', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('debit_card_transactions', ['amount' => 100]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        $otherCard = DebitCard::factory()->create();
        $payload = [
            'debit_card_id' => $otherCard->id,
            'amount' => 100,
            'currency_code' => 'SGD'
        ];

        $response = $this->postJson('/api/debit-card-transactions', $payload);

        $response->assertStatus(403);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        $transaction = DebitCardTransaction::factory()
            ->for($this->debitCard)
            ->create();

        $response = $this->getJson("/api/debit-card-transactions/{$transaction->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $transaction->id);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        $otherCard = DebitCard::factory()->create();
        $transaction = DebitCardTransaction::factory()->for($otherCard)->create();

        $response = $this->getJson("/api/debit-card-transactions/{$transaction->id}");

        $response->assertStatus(403);
    }
}
