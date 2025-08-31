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
        DebitCardTransaction::factory()->count(3)->create([
            'debit_card_id' => $this->debitCard->id
        ]);

        $this->actingAs($this->user, 'api')
                    ->getJson('/api/debit-card-transactions',['debit_card_id' => $this->debitCard->id])
                    ->assertStatus(200)
                    ->assertJsonStructure([
                    '*' => ['amount', 'currency_code']
                ]);
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

        $response = $this->actingAs($this->user, 'api')
                        ->getJson('/api/debit-card-transactions',['debit_card_id' => $otherDebitCard->id])
                        ->assertStatus(403);
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $currencyCode = DebitCardTransaction::CURRENCIES[array_rand(DebitCardTransaction::CURRENCIES)];
        $payload = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 12345,
            'currency_code' => $currencyCode
        ];

        $this->actingAs($this->user, 'api')
             ->postJson('/api/debit-card-transactions', $payload)
             ->assertStatus(201)
             ->assertJsonFragment([
                 'amount' => 12345,
                 'currency_code' => $payload['currency_code']
             ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $currencyCode = DebitCardTransaction::CURRENCIES[array_rand(DebitCardTransaction::CURRENCIES)];

        $payload = [
            'debit_card_id' => $otherDebitCard->id,
            'amount' => 12345,
            'currency_code' => $currencyCode
        ];

        $this->actingAs($this->user, 'api')
             ->postJson('/api/debit-card-transactions', $payload)
             ->assertStatus(403);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id
        ]);

        $this->actingAs($this->user, 'api')
             ->getJson('/api/debit-card-transactions/' . $transaction->id)
             ->assertStatus(200)
             ->assertJsonFragment([
                 'amount' => (string)$transaction->amount,
                 'currency_code' => $transaction->currency_code,
             ]);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $otherDebitCard->id
        ]);

        $this->actingAs($this->user, 'api')
             ->getJson('/api/debit-card-transactions/' . $transaction->id)
             ->assertStatus(403);
    }

    // Extra bonus for extra tests :)
    public function testCustomerCannotCreateADebitCardTransactionWithInvalidData()
    {
        $payload = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 10000,
            'currency_code' => 'INVALID' // Invalid currency code
        ];

        $this->actingAs($this->user, 'api')
             ->postJson('/api/debit-card-transactions', $payload)
             ->assertStatus(422)
             ->assertJsonValidationErrors(['amount', 'currency_code']);
    }
}
