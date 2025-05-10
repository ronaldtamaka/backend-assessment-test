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
        // Create transactions for the debit card
        DebitCardTransaction::factory()->count(3)->create([
            'debit_card_id' => $this->debitCard->id
        ]);

        $response = $this->getJson('/api/debit-card-transactions');

        $response->assertStatus(200);
        $response->assertJsonCount(3);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // Create another user and a debit card for that user
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);

        // Create transactions for the other debit card
        DebitCardTransaction::factory()->count(3)->create([
            'debit_card_id' => $otherDebitCard->id
        ]);

        $response = $this->getJson('/api/debit-card-transactions');

        $response->assertStatus(200);
        $response->assertJsonCount(0); // The other user's transactions should not be shown
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $payload = [
            'amount' => 1000,
            'type' => 'debit',
        ];

        $response = $this->postJson('/api/debit-card-transactions', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 1000,
            'type' => 'debit',
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // Create another user and debit card
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $payload = [
            'amount' => 1000,
            'type' => 'debit',
            'debit_card_id' => $otherDebitCard->id
        ];

        $response = $this->postJson('/api/debit-card-transactions', $payload);

        $response->assertStatus(403); // Forbidden since it's not the authenticated user's debit card
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id
        ]);

        $response = $this->getJson("/api/debit-card-transactions/{$transaction->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $transaction->id,
            'amount' => $transaction->amount,
            'type' => $transaction->type,
        ]);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // Create another user and a transaction on their debit card
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);
        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $otherDebitCard->id
        ]);

        $response = $this->getJson("/api/debit-card-transactions/{$transaction->id}");

        $response->assertStatus(403); // Forbidden since it's another customer's transaction
    }

    // Extra bonus for extra tests :)

    public function testCustomerCannotCreateTransactionWithInvalidData()
    {
        $payload = [
            'amount' => 'invalid_amount',
            'type' => 'debit',
        ];

        $response = $this->postJson('/api/debit-card-transactions', $payload);

        $response->assertStatus(422); // Validation error
        $response->assertJsonValidationErrors(['amount']);
    }

    public function testCustomerCannotCreateTransactionWithoutAmount()
    {
        $payload = [
            'type' => 'debit',
        ];

        $response = $this->postJson('/api/debit-card-transactions', $payload);

        $response->assertStatus(422); // Validation error
        $response->assertJsonValidationErrors(['amount']);
    }

    public function testCustomerCannotCreateTransactionWithInvalidCardId()
    {
        $payload = [
            'amount' => 1000,
            'type' => 'debit',
            'debit_card_id' => 9999, // non-existing debit card ID
        ];

        $response = $this->postJson('/api/debit-card-transactions', $payload);

        $response->assertStatus(404); // Not found since the debit card doesn't exist
    }
}
