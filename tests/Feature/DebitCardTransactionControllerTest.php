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

        $response = $this->getJson('/api/debit-card-transactions');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);
        DebitCardTransaction::factory()->create(['debit_card_id' => $otherCard->id]);

        $response = $this->getJson('/api/debit-card-transactions');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

   public function testCustomerCanCreateADebitCardTransaction()
    {
        $payload = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 5000,
            'currency_code' => 'VND',
            // 'transacted_at' => now()->toDateTimeString(),
        ];

        $response = $this->postJson('/api/debit-card-transactions', $payload);

        $response->assertCreated();
        $this->assertDatabaseHas('debit_card_transactions', $payload);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);

        $payload = [
            'debit_card_id' => $otherCard->id,
            'amount' => 2000,
            'description' => 'Fraudulent',
        ];

        $response = $this->postJson('/api/debit-card-transactions', $payload);

        $response->assertForbidden();
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        $user = User::factory()->create();

        $debitCard = DebitCard::factory()->for($user)->create();

        $transaction = DebitCardTransaction::factory()->for($debitCard)->create();

        $this->actingAs($user);

        $response = $this->getJson("/api/debit-card-transactions/{$transaction->id}");

        $response->assertOk();

        $response->assertJsonPath('data.id', $transaction->id);
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
