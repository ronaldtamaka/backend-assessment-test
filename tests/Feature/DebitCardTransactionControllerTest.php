<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\Rule;
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
        $transaction1 = DebitCardTransaction::factory()->create(['debit_card_id' => $this->debitCard->id]);
        $transaction2 = DebitCardTransaction::factory()->create(['debit_card_id' => $this->debitCard->id]);

        $response = $this->getJson('/api/debit-card-transactions', [
            'debit_card_id' => $this->debitCard->id
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(2, $data);
        $this->assertEquals($transaction1->amount, $data[0]['amount']);
        $this->assertEquals($transaction2->amount, $data[1]['amount']);
    }

    public function testCustomerCanSeeEmptyListOfDebitCardTransactionsWhenNoneExist()
    {
        $response = $this->getJson('/api/debit-card-transactions', [
            'debit_card_id' => $this->debitCard->id
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(0, $data);
    }

    public function testCustomerCannotSeeTransactionsOfOtherCustomersDebitCard()
    {
        DebitCardTransaction::factory()->create(['debit_card_id' => $this->otherUserDebitCard->id]);

        $response = $this->getJson('/api/debit-card-transactions', [
            'debit_card_id' => $this->otherUserDebitCard->id
        ]);

        $response->assertStatus(403);
    }

    public function testMissingDebitCardIdParameterReturnValidationError()
    {
        $response = $this->getJson('/api/debit-card-transactions');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('debit_card_id');
    }

    public function testInvalidDebitCardIdReturnValidationError()
    {
        $response = $this->getJson('/api/debit-card-transactions', [
            'debit_card_id' => 99999
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('debit_card_id');
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $transactionData = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 10000,
            'currency_code' => DebitCardTransaction::CURRENCY_VND,
        ];

        $response = $this->postJson('/api/debit-card-transactions', $transactionData);

        $response->assertStatus(201);
        $response->assertJsonPath('amount', 10000);
        $response->assertJsonPath('currency_code', DebitCardTransaction::CURRENCY_VND);
        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 10000,
        ]);
    }

    public function testCustomerCanCreateTransactionWithDifferentCurrencies()
    {
        foreach (DebitCardTransaction::CURRENCIES as $currency) {
            $response = $this->postJson('/api/debit-card-transactions', [
                'debit_card_id' => $this->debitCard->id,
                'amount' => 5000,
                'currency_code' => $currency,
            ]);

            $response->assertStatus(201);
            $response->assertJsonPath('currency_code', $currency);
        }

        $this->assertCount(4, DebitCardTransaction::where('debit_card_id', $this->debitCard->id)->get());
    }

    public function testCustomerCannotCreateTransactionForOtherCustomersDebitCard()
    {
        $transactionData = [
            'debit_card_id' => $this->otherUserDebitCard->id,
            'amount' => 10000,
            'currency_code' => DebitCardTransaction::CURRENCY_VND,
        ];

        $response = $this->postJson('/api/debit-card-transactions', $transactionData);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('debit_card_transactions', [
            'debit_card_id' => $this->otherUserDebitCard->id,
        ]);
    }

    public function testCustomerCannotCreateTransactionWithoutDebitCardId()
    {
        $transactionData = [
            'amount' => 10000,
            'currency_code' => DebitCardTransaction::CURRENCY_VND,
        ];

        $response = $this->postJson('/api/debit-card-transactions', $transactionData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('debit_card_id');
    }

    public function testCustomerCannotCreateTransactionWithInvalidDebitCardId()
    {
        $transactionData = [
            'debit_card_id' => 99999,
            'amount' => 10000,
            'currency_code' => DebitCardTransaction::CURRENCY_VND,
        ];

        $response = $this->postJson('/api/debit-card-transactions', $transactionData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('debit_card_id');
    }

    public function testCustomerCannotCreateTransactionWithoutAmount()
    {
        $transactionData = [
            'debit_card_id' => $this->debitCard->id,
            'currency_code' => DebitCardTransaction::CURRENCY_VND,
        ];

        $response = $this->postJson('/api/debit-card-transactions', $transactionData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('amount');
    }

    public function testCustomerCannotCreateTransactionWithNonIntegerAmount()
    {
        $transactionData = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 'not-a-number',
            'currency_code' => DebitCardTransaction::CURRENCY_VND,
        ];

        $response = $this->postJson('/api/debit-card-transactions', $transactionData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('amount');
    }

    public function testCustomerCannotCreateTransactionWithoutCurrencyCode()
    {
        $transactionData = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 10000,
        ];

        $response = $this->postJson('/api/debit-card-transactions', $transactionData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('currency_code');
    }

    public function testCustomerCannotCreateTransactionWithInvalidCurrencyCode()
    {
        $transactionData = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 10000,
            'currency_code' => 'INVALID',
        ];

        $response = $this->postJson('/api/debit-card-transactions', $transactionData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('currency_code');
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        $transaction = DebitCardTransaction::factory()->create(['debit_card_id' => $this->debitCard->id]);

        $response = $this->getJson("/api/debit-card-transactions/{$transaction->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('amount', $transaction->amount);
        $response->assertJsonPath('currency_code', $transaction->currency_code);
    }

    public function testCustomerCannotSeeTransactionAttachedToOtherCustomersDebitCard()
    {
        $transaction = DebitCardTransaction::factory()->create(['debit_card_id' => $this->otherUserDebitCard->id]);

        $response = $this->getJson("/api/debit-card-transactions/{$transaction->id}");

        $response->assertStatus(403);
    }

    public function testShowNonExistentTransactionReturn404()
    {
        $response = $this->getJson('/api/debit-card-transactions/99999');

        $response->assertStatus(404);
    }

    public function testDebitCardTransactionResourceContainsCorrectFields()
    {
        $transaction = DebitCardTransaction::factory()->create(['debit_card_id' => $this->debitCard->id]);

        $response = $this->getJson("/api/debit-card-transactions/{$transaction->id}");

        $response->assertJsonStructure([
            'amount',
            'currency_code',
        ]);
    }

    public function testMultipleTransactionsCanBeCreatedForSameDebitCard()
    {
        $amounts = [1000, 2000, 3000, 5000];
        foreach ($amounts as $amount) {
            $this->postJson('/api/debit-card-transactions', [
                'debit_card_id' => $this->debitCard->id,
                'amount' => $amount,
                'currency_code' => DebitCardTransaction::CURRENCY_VND,
            ]);
        }

        $response = $this->getJson('/api/debit-card-transactions', [
            'debit_card_id' => $this->debitCard->id
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(4, $data);
    }

    public function testTransactionIsolationBetweenUsers()
    {
        $transaction1 = DebitCardTransaction::factory()->create(['debit_card_id' => $this->debitCard->id]);
        $transaction2 = DebitCardTransaction::factory()->create(['debit_card_id' => $this->otherUserDebitCard->id]);

        $response = $this->getJson("/api/debit-card-transactions/{$transaction2->id}");

        $response->assertStatus(403);

        $response = $this->getJson("/api/debit-card-transactions/{$transaction1->id}");

        $response->assertStatus(200);
    }
}
