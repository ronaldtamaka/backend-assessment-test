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
            'debit_card_id' => $this->debitCard->id,
        ]);

        $response = $this->getJson("/api/debit-card-transactions?debit_card_id={$this->debitCard->id}");
        $response->assertOk()
                 ->assertJsonCount(3);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);
        DebitCardTransaction::factory()->count(3)->create([
            'debit_card_id' => $otherCard->id,
        ]);

        $response = $this->getJson("/api/debit-card-transactions?debit_card_id={$this->debitCard->id}");

        $response->assertOk()
                 ->assertJsonCount(0);
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $payload = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 100,
            'currency_code' => 'IDR'
        ];

        $response = $this->postJson('/api/debit-card-transactions', $payload);
        $responseData = $response->json();
        $response->assertCreated()
                 ->assertJsonFragment([
                     'amount' => 100,
                     'currency_code' => 'IDR',
                 ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);

        $payload = [
            'debit_card_id' => $otherCard->id,
            'amount' => 100.00,
            'description' => 'Unauthorized transaction'
        ];

        $response = $this->postJson('/api/debit-card-transactions', $payload);

        $response->assertForbidden();
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        $transaction = DebitCardTransaction::factory()
            ->for($this->debitCard)
            ->createOne();

        $response = $this->getJson("/api/debit-card-transactions/{$transaction->id}");

        $response->assertOk()
                 ->assertJsonStructure(['amount', 'currency_code'])
                 ->assertJson([
                    'amount' => $transaction->amount,
                    'currency_code' => $transaction->currency_code,
        ]);
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

    // Bonus Test: Ensure validation works
    public function testCustomerCannotCreateTransactionWithInvalidData()
    {
        $payload = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => null,
            'currency_code' => ''
        ];

        $response = $this->postJson('/api/debit-card-transactions', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['amount', 'currency_code']);
    }
}
