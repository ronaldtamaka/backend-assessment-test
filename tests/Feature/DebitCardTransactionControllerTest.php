<?php

namespace Tests\Feature;

use App\Models\DebitCard;
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

        $response = $this->getJson('/api/debit-card-transactions');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'amount',
                        'currency_code',
                    ]
                ]
            ]);

        foreach ($response->json('data') as $transaction) {
            $debitCard = DebitCard::find(DebitCardTransaction::find($transaction['id'])->debit_card_id);
            $this->assertEquals($this->user->id, $debitCard->user_id);
        }
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $ownTransactions = DebitCardTransaction::factory()->count(2)->create([
            'debit_card_id' => $this->debitCard->id
        ]);

        $otherTransactions = DebitCardTransaction::factory()->count(3)->create([
            'debit_card_id' => $otherDebitCard->id
        ]);

        $response = $this->getJson('/api/debit-card-transactions');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $returnedTransactionIds = collect($response->json('data'))->pluck('id');
        foreach ($ownTransactions as $transaction) {
            $this->assertTrue($returnedTransactionIds->contains($transaction->id));
        }
        foreach ($otherTransactions as $transaction) {
            $this->assertFalse($returnedTransactionIds->contains($transaction->id));
        }
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
                'data' => [
                    'amount',
                    'currency_code',
                    
                ]
            ])
            ->assertJson([
                'data' => [
                    'amount' => 150000,
                    'currency_code' => 'IDR',
                ]
            ]);

        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 150000,
            'currency_code' => 'IDR',
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
    }

    // Extra bonus for extra tests :)
}
