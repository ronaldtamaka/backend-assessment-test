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

    /**
     * Current User Model
     *
     * @var User
     */
    protected User $user;

    /**
     * Current Debit Card Model
     *
     * @var DebitCard
     */
    protected DebitCard $debitCard;

    /**
     * Set up method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);
        Passport::actingAs($this->user);
    }

    /**
     * Test Customer Can See a List of Debit Card Transactions
     *
     * @return void
     */
    public function testCustomerCanSeeAListOfDebitCardTransactions(): void
    {
        DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id
        ]);

        $response = $this->json('GET', '/api/debit-card-transactions', [
            'debit_card_id' => $this->debitCard->id
        ]);
        $response
            ->assertOk()
            ->assertJsonCount(1);
    }

    /**
     * Test Customer Cannot See a List of Debit Card Transactions of Other Customer Debit Card
     *
     * @return void
     */
    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard(): void
    {
        $otherUser = User::factory()->create();
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->json('GET', '/api/debit-card-transactions', [
            'debit_card_id' => $debitCard->id
        ]);
        $response
            ->assertForbidden();
    }

    /**
     * Test Customer Can Create a Debit Card Transaction
     *
     * @return void
     */
    public function testCustomerCanCreateADebitCardTransaction(): void
    {
        $response = $this->postJson('/api/debit-card-transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => $this->faker()->randomNumber(),
            'currency_code' => $this->faker()->randomElement(DebitCardTransaction::CURRENCIES),
        ]);
        $response->assertCreated();
    }

    /**
     * Test Customer Cannot Create a Debit Card Transaction to Other Customer Debit Card
     *
     * @return void
     */
    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard(): void
    {
        $otherUser = User::factory()->create();
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->postJson('/api/debit-card-transactions', [
            'debit_card_id' => $debitCard->id,
            'amount' => $this->faker()->randomNumber(),
            'currency_code' => $this->faker()->randomElement(DebitCardTransaction::CURRENCIES),
        ]);
        $response->assertForbidden();
    }

    /**
     * Test Customer Can See a Debit Card Transaction
     *
     * @return void
     */
    public function testCustomerCanSeeADebitCardTransaction(): void
    {
        $debitCardTransaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id
        ]);

        $response = $this->getJson("/api/debit-card-transactions/{$debitCardTransaction->id}");
        $response
            ->assertOk();

        $this->assertDatabaseHas('debit_card_transactions', [
            'amount' => $response->json('amount'),
            'debit_card_id' => $this->debitCard->id,
        ]);
    }

    /**
     * Test Customer Cannot See a Debit Card Transaction Attached to Other Customer Debit Card
     *
     * @return void
     */
    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard(): void
    {
        $otherUser = User::factory()->create();
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $otherUser->id
        ]);
        $debitCardTransaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $debitCard->id
        ]);

        $response = $this->getJson("/api/debit-card-transactions/{$debitCardTransaction->id}");
        $response
            ->assertForbidden();
    }

    // Extra bonus for extra tests :)
}
