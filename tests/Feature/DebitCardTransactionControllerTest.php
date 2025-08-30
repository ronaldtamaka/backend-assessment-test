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
    protected User $otherUser;
    protected DebitCard $debitCard;
    protected DebitCard $otherUserDebitCard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);
        $this->otherUserDebitCard = DebitCard::factory()->create([
            'user_id' => $this->otherUser->id
        ]);
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCardTransactions()
    {
        // Create transactions for the authenticated user's debit card
        $transaction1 = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id,
            'amount' => 1000,
            'currency_code' => 'IDR'
        ]);
        $transaction2 = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id,
            'amount' => 2000,
            'currency_code' => 'SGD'
        ]);

        // Create a transaction for another user's debit card (should not be visible)
        DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->otherUserDebitCard->id,
            'amount' => 3000,
            'currency_code' => 'VND'
        ]);

        $response = $this->getJson('/api/debit-card-transactions?debit_card_id=' . $this->debitCard->id);

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonFragment([
                'id' => $transaction1->id,
                'amount' => 1000,
                'currency_code' => 'IDR'
            ])
            ->assertJsonFragment([
                'id' => $transaction2->id,
                'amount' => 2000,
                'currency_code' => 'SGD'
            ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // Create transactions for other user's debit card
        DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->otherUserDebitCard->id,
            'amount' => 1000,
            'currency_code' => 'IDR'
        ]);

        $response = $this->getJson('/api/debit-card-transactions?debit_card_id=' . $this->otherUserDebitCard->id);

        $response->assertStatus(200)
            ->assertJsonCount(0);
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $data = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 1000,
            'currency_code' => 'IDR'
        ];

        $response = $this->postJson('/api/debit-card-transactions', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'debit_card_id',
                'amount',
                'currency_code'
            ])
            ->assertJsonFragment([
                'debit_card_id' => $this->debitCard->id,
                'amount' => 1000,
                'currency_code' => 'IDR'
            ]);

        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 1000,
            'currency_code' => 'IDR'
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        $data = [
            'debit_card_id' => $this->otherUserDebitCard->id,
            'amount' => 1000,
            'currency_code' => 'IDR'
        ];

        $response = $this->postJson('/api/debit-card-transactions', $data);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('debit_card_transactions', [
            'debit_card_id' => $this->otherUserDebitCard->id,
            'amount' => 1000,
            'currency_code' => 'IDR'
        ]);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id,
            'amount' => 1000,
            'currency_code' => 'IDR'
        ]);

        $response = $this->getJson("/api/debit-card-transactions/{$transaction->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $transaction->id,
                'debit_card_id' => $this->debitCard->id,
                'amount' => 1000,
                'currency_code' => 'IDR'
            ]);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->otherUserDebitCard->id,
            'amount' => 1000,
            'currency_code' => 'IDR'
        ]);

        $response = $this->getJson("/api/debit-card-transactions/{$transaction->id}");

        $response->assertStatus(403);
    }

    // Extra bonus for extra tests :)
    public function testCustomerCannotCreateTransactionWithInvalidData()
    {
        $data = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 'invalid_amount', // Should be integer
            'currency_code' => 'INVALID_CURRENCY' // Should be valid currency
        ];

        $response = $this->postJson('/api/debit-card-transactions', $data);

        $response->assertStatus(422);
    }

    public function testCustomerCannotCreateTransactionWithNonExistentDebitCard()
    {
        $data = [
            'debit_card_id' => 99999, // Non-existent debit card ID
            'amount' => 1000,
            'currency_code' => 'IDR'
        ];

        $response = $this->postJson('/api/debit-card-transactions', $data);

        $response->assertStatus(422);
    }

    public function testCustomerCannotCreateTransactionWithoutRequiredFields()
    {
        $data = [
            'debit_card_id' => $this->debitCard->id
            // Missing amount and currency_code
        ];

        $response = $this->postJson('/api/debit-card-transactions', $data);

        $response->assertStatus(422);
    }
}
