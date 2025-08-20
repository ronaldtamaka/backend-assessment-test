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

        $response = $this->getJson('/api/debit-card-transactions?debit_card_id=' . $this->debitCard->id);

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data')
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'amount',
                             'currency_code',
                         ]
                     ]
                 ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        
        DebitCardTransaction::factory()->count(2)->create([
            'debit_card_id' => $otherDebitCard->id,
        ]);

        $response = $this->getJson('/api/debit-card-transactions?debit_card_id=' . $otherDebitCard->id);

        $response->assertStatus(403);
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $transactionData = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 10000,
            'currency_code' => 'IDR',
        ];

        $response = $this->postJson('/api/debit-card-transactions', $transactionData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'amount',
                         'currency_code',
                     ]
                 ])
                 ->assertJson([
                     'data' => [
                         'amount' => 10000,
                         'currency_code' => 'IDR',
                     ]
                 ]);

        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 10000,
            'currency_code' => 'IDR',
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $transactionData = [
            'debit_card_id' => $otherDebitCard->id,
            'amount' => 10000,
            'currency_code' => 'IDR',
        ];

        $response = $this->postJson('/api/debit-card-transactions', $transactionData);

        $response->assertStatus(403);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id,
        ]);

        $response = $this->getJson('/api/debit-card-transactions/' . $transaction->id);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'amount',
                         'currency_code',
                     ]
                 ])
                 ->assertJson([
                     'data' => [
                         'id' => $transaction->id,
                         'amount' => $transaction->amount,
                         'currency_code' => $transaction->currency_code,
                     ]
                 ]);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        
        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $otherDebitCard->id,
        ]);

        $response = $this->getJson('/api/debit-card-transactions/' . $transaction->id);

        $response->assertStatus(403);
    }

    
    
    public function testCustomerCannotCreateDebitCardTransactionWithInvalidData()
    {
        
        $response = $this->postJson('/api/debit-card-transactions', []);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['debit_card_id', 'amount', 'currency_code']);

        
        $response = $this->postJson('/api/debit-card-transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 10000,
            'currency_code' => 'INVALID',
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['currency_code']);

        
        $response = $this->postJson('/api/debit-card-transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 'invalid_amount',
            'currency_code' => 'IDR',
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['amount']);
    }
    
    public function testCustomerCanCreateTransactionsWithDifferentCurrencies()
    {
        $currencies = ['IDR', 'SGD', 'THB', 'VND'];
        
        foreach ($currencies as $currency) {
            $transactionData = [
                'debit_card_id' => $this->debitCard->id,
                'amount' => 10000,
                'currency_code' => $currency,
            ];

            $response = $this->postJson('/api/debit-card-transactions', $transactionData);
            $response->assertStatus(201);
            
            $this->assertDatabaseHas('debit_card_transactions', [
                'debit_card_id' => $this->debitCard->id,
                'currency_code' => $currency,
            ]);
        }
    }
    
    public function testCustomerCannotCreateTransactionForNonExistentDebitCard()
    {
        $transactionData = [
            'debit_card_id' => 99999, 
            'amount' => 10000,
            'currency_code' => 'IDR',
        ];

        $response = $this->postJson('/api/debit-card-transactions', $transactionData);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['debit_card_id']);
    }
}
