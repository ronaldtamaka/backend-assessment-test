<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardTransactionControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

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
        DebitCardTransaction::factory(5)
            ->for($this->debitCard)
            ->create();

        $response = $this->getJson("/api/debit-card-transactions?debit_card_id={$this->debitCard->id}");

        $response
            ->assertOk()
            ->assertJsonCount(5)
            ->assertJsonStructure([
                '*' => ['amount', 'currency_code'],
            ]);

        $this->assertDatabaseCount('debit_card_transactions', 5);
        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
        ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        $card = DebitCard::factory()->active()->createOne();

        DebitCardTransaction::factory(5)
            ->for($card)
            ->create();

        $response = $this->getJson("/api/debit-card-transactions?debit_card_id={$card->id}");

        $response->assertForbidden();

        $this->assertDatabaseCount('debit_card_transactions', 5);
        $this->assertDatabaseMissing('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
        ]);
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $response = $this->postJson("/api/debit-card-transactions", [
            'debit_card_id' => $this->debitCard->id,
            'amount' => $amount = $this->faker->randomNumber(),
            'currency_code' => $currency = $this->faker->randomElement(DebitCardTransaction::CURRENCIES),
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure(['amount', 'currency_code'])
            ->assertJson([
               'amount' => $amount,
               'currency_code' => $currency,
            ]);

        $this->assertDatabaseCount('debit_card_transactions', 1);
        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => $amount,
            'currency_code' => $currency,
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        $card = DebitCard::factory()->active()->createOne();

        $response = $this->postJson("/api/debit-card-transactions", [
            'debit_card_id' => $card->id,
            'amount' => $amount = $this->faker->randomNumber(),
            'currency_code' => $currency = $this->faker->randomElement(DebitCardTransaction::CURRENCIES),
        ]);

        $response->assertForbidden();

        $this->assertDatabaseCount('debit_card_transactions', 0);
        $this->assertDatabaseMissing('debit_card_transactions', [
            'debit_card_id' => $card->id,
            'amount' => $amount,
            'currency_code' => $currency,
        ]);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        $transaction = DebitCardTransaction::factory()
            ->for($this->debitCard)
            ->createOne();

        $response = $this->getJson("/api/debit-card-transactions/{$transaction->id}");

        $response
            ->assertOk()
            ->assertJsonStructure(['amount', 'currency_code'])
            ->assertJson([
                'amount' => $transaction->amount,
                'currency_code' => $transaction->currency_code,
            ]);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        $card = DebitCard::factory()->active()->createOne();
        $transaction = DebitCardTransaction::factory()->for($card)->createOne();

        $response = $this->getJson("/api/debit-card-transactions/{$transaction->id}");

        $response->assertForbidden();
    }

    public function testCustomerCannotCreateADebitCardTransactionWithInvalidAmount()
    {
        $response = $this->postJson("/api/debit-card-transactions", [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 'invalid-amount',
            'currency_code' => $currency = $this->faker->randomElement(DebitCardTransaction::CURRENCIES),
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount'])
            ->assertJsonMissingValidationErrors(['debit_card_id', 'currency_code']);

        $this->assertDatabaseCount('debit_card_transactions', 0);
        $this->assertDatabaseMissing('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 'invalid-amount',
            'currency_code' => $currency,
        ]);
    }
    public function testCustomerCannotCreateADebitCardTransactionWithMissingAmount()
    {
        $response = $this->postJson("/api/debit-card-transactions", [
            'debit_card_id' => $this->debitCard->id,
            'currency_code' => $currency = $this->faker->randomElement(DebitCardTransaction::CURRENCIES),
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount'])
            ->assertJsonMissingValidationErrors(['debit_card_id', 'currency_code']);

        $this->assertDatabaseCount('debit_card_transactions', 0);
        $this->assertDatabaseMissing('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'currency_code' => $currency,
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionWithInvalidCurrencyType()
    {
        $response = $this->postJson("/api/debit-card-transactions", [
            'debit_card_id' => $this->debitCard->id,
            'amount' => $amount = $this->faker->randomNumber(),
            'currency_code' => 'invalid-currency-code',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['currency_code'])
            ->assertJsonMissingValidationErrors(['debit_card_id', 'amount']);

        $this->assertDatabaseCount('debit_card_transactions', 0);
        $this->assertDatabaseMissing('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => $amount,
            'currency_code' => 'invalid-currency-code',
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionWithMissingCurrencyCode()
    {
        $response = $this->postJson("/api/debit-card-transactions", [
            'debit_card_id' => $this->debitCard->id,
            'amount' => $amount = $this->faker->randomNumber(),
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['currency_code'])
            ->assertJsonMissingValidationErrors(['debit_card_id', 'amount']);

        $this->assertDatabaseCount('debit_card_transactions', 0);
        $this->assertDatabaseMissing('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => $amount,
        ]);
    }
    public function testCustomerCannotCreateADebitCardTransactionWithInvalidDebitCardId()
    {
        $response = $this->postJson("/api/debit-card-transactions", [
            'debit_card_id' => 'invalid-debit-card-id',
            'amount' => $amount = $this->faker->randomNumber(),
            'currency_code' => $currency = $this->faker->randomElement(DebitCardTransaction::CURRENCIES),
        ]);

        $response->assertForbidden();

        $this->assertDatabaseCount('debit_card_transactions', 0);
        $this->assertDatabaseMissing('debit_card_transactions', [
            'debit_card_id' => 'invalid-debit-card-id',
            'amount' => $amount,
            'currency_code' => $currency,
        ]);
    }
}
