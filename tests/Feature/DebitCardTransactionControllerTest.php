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
        // Create transactions for the authenticated user's debit card
        $transactions = DebitCardTransaction::factory()->count(3)->create([
            'debit_card_id' => $this->debitCard->id
        ]);

        // Create another debit card for the same user with transactions
        $anotherCard = DebitCard::factory()->create(['user_id' => $this->user->id]);
        DebitCardTransaction::factory()->count(2)->create([
            'debit_card_id' => $anotherCard->id
        ]);

        // Create transactions for another user (should not be visible)
        $otherUser = User::factory()->create();
        $otherCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);
        DebitCardTransaction::factory()->create([
            'debit_card_id' => $otherCard->id
        ]);

        $response = $this->getJson('/api/debit-card-transactions?debit_card_id=' . $this->debitCard->id);

        $response->assertStatus(200)
                 ->assertJsonCount(3) // 3 transactions for specific debit card
                 ->assertJsonStructure([
                     '*' => [
                         'amount',
                         'currency_code'
                     ]
                 ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // Create transactions for another user's debit card
        $otherUser = User::factory()->create();
        $otherCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);
        DebitCardTransaction::factory()->count(3)->create([
            'debit_card_id' => $otherCard->id
        ]);

        // Create one transaction for authenticated user
        $myTransaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id
        ]);

        $response = $this->getJson('/api/debit-card-transactions?debit_card_id=' . $this->debitCard->id);

        $response->assertStatus(200)
                 ->assertJsonCount(1);

        // Verify only authenticated user's transaction is returned
        $this->assertEquals($myTransaction->amount, $response->json('0.amount'));
        $this->assertEquals($myTransaction->currency_code, $response->json('0.currency_code'));
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $transactionData = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 10000,
            'currency_code' => 'SGD'
        ];

        $response = $this->postJson('/api/debit-card-transactions', $transactionData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'amount',
                     'currency_code'
                 ]);

        // Verify transaction was created in database
        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 10000,
            'currency_code' => 'SGD'
        ]);

        $this->assertEquals(10000, $response->json('amount'));
        $this->assertEquals('SGD', $response->json('currency_code'));
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // Create debit card for another user
        $otherUser = User::factory()->create();
        $otherCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);

        $transactionData = [
            'debit_card_id' => $otherCard->id,
            'amount' => 10000,
            'currency_code' => 'SGD'
        ];

        $response = $this->postJson('/api/debit-card-transactions', $transactionData);

        // Should return forbidden or validation error
        $this->assertContains($response->getStatusCode(), [403, 422]);

        // Verify transaction was not created
        $this->assertDatabaseMissing('debit_card_transactions', [
            'debit_card_id' => $otherCard->id,
            'amount' => 10000,
            'currency_code' => 'SGD'
        ]);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id,
            'amount' => 15000,
            'currency_code' => 'VND'
        ]);

        $response = $this->getJson("/api/debit-card-transactions/{$transaction->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'amount',
                     'currency_code'
                 ]);

        $this->assertEquals($transaction->amount, $response->json('amount'));
        $this->assertEquals($transaction->currency_code, $response->json('currency_code'));
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // Create debit card and transaction for another user
        $otherUser = User::factory()->create();
        $otherCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);
        $otherTransaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $otherCard->id
        ]);

        $response = $this->getJson("/api/debit-card-transactions/{$otherTransaction->id}");

        // Should return forbidden or not found
        $this->assertContains($response->getStatusCode(), [403, 404]);
    }

    // Extra bonus for extra tests :)
}
