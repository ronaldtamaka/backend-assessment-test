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
        $transactions = DebitCardTransaction::factory()->count(3)->create([
            'debit_card_id' => $this->debitCard->id
        ]);

        $response = $this->getJson('/api/debit-card-transactions?debit_card_id='.$this->debitCard->id);

        $response->assertStatus(200);

        $json = $response->json();

        $this->assertCount(3, $json);

        foreach ($json as $index => $transactionData) {
            $this->assertArrayHasKey('amount', $transactionData);
            $this->assertArrayHasKey('currency_code', $transactionData);

            $this->assertEquals($transactions[$index]->amount, $transactionData['amount']);
            $this->assertEquals($transactions[$index]->currency_code, $transactionData['currency_code']);
        }
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);

        DebitCardTransaction::factory()->count(3)->create([
            'debit_card_id' => $otherDebitCard->id
        ]);

        $response = $this->getJson('/api/debit-card-transactions?debit_card_id='.$otherDebitCard->id);

        $response->assertStatus(403);
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $transactionData = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 150000,
            'currency_code' => 'IDR',
        ];

        $response = $this->postJson('/api/debit-card-transactions', $transactionData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                    'amount',
                    'currency_code',
                    
            ])
            ->assertJson([
                    'amount' => 150000,
                    'currency_code' => 'IDR',
            ]);

        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 150000,
            'currency_code' => 'IDR',
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $transactionData = [
            'debit_card_id' => $otherDebitCard->id,
            'amount' => 150000,
            'currency_code' => 'IDR',
            
        ];

        $response = $this->postJson('/api/debit-card-transactions', $transactionData);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('debit_card_transactions', [
            'debit_card_id' => $otherDebitCard->id,
            'amount' => 150000,
            'currency_code' => 'IDR',
        ]);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id,
            'amount' => 150000,
            'currency_code' => 'IDR',
        ]);

        $response = $this->getJson("/api/debit-card-transactions/{$transaction->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                    'amount',
                    'currency_code'
                    
            ])
            ->assertJson([
                    'amount' => $transaction->amount,
                    'currency_code' => $transaction->currency_code
            ]);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);
        $otherTransaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $otherDebitCard->id
        ]);

        $response = $this->getJson("/api/debit-card-transactions/{$otherTransaction->id}");

        $response->assertStatus(403);
    }

    // Extra bonus for extra tests :)
}
