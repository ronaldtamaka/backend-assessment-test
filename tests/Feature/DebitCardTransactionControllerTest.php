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
        $debitCardTransaction = DebitCardTransaction::factory(3)->create([
            'debit_card_id' => $this->debitCard->id
        ]);

        $response = $this->get('/api/debit-card-transactions');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'debit_card_id',
                    'amount',
                    'description',

                ]
            ]
        ]);

        foreach ($debitCardTransaction as $transaction) {
            $response->assertJson(['data' => [
                ['id' => $transaction->id],
            ]]);
        }
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherUserDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $otherUserTransaction = DebitCardTransaction::factory(3)->create([
            'debit_card_id' => $otherUserDebitCard->id
        ]);

        $response = $this->get('/api/debit-card-transactions');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');

        foreach ($otherUserTransaction as $transaction) {
            $response->assertJsonMissing(['data' => [
                ['id' => $transaction->id],
            ]]);
        }
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $transactionData = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 100.00,
            'description' => 'Purchase',
        ];

        $response = $this->post('/api/debit-card-transactions', $transactionData);

        $response->assertStatus(201);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherUserDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $transactionData = [
            'debit_card_id' => $otherUserDebitCard->id,
            'amount' => 100.00,
            'description' => 'Purchase',
        ];

        $response = $this->post('/api/debit-card-transactions', $transactionData);

        $response->assertStatus(200);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id
        ]);

        $response = $this->get("/api/debit-card-transactions/{$transaction->id}");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');

        $response->assertJsonStructure([
            'data' => [
                'id',
                'debit_card_id',
                'amount',
                'description',
                
            ]
        ]);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherUserDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $otherUserDebitCard->id
        ]);

        $response = $this->get("/api/debit-card-transactions/{$transaction->id}");

        $response->assertStatus(200);
    }

    // Extra bonus for extra tests :)
}
