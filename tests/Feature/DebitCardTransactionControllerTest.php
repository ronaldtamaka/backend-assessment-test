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
        // get /debit-card-transactions
        $card = DebitCard::factory()->create([
            'user_id' => $this->user->id,
        ]);

        DebitCardTransaction::factory()->create([
            'debit_card_id' => $card->id,
            'amount' => 100000,
            'currency_code' => 'IDR',
        ]);
        $debit_card_id = $card->id;
        $response = $this->getJson("/api/debit-card-transactions?debit_card_id={$debit_card_id}");

        $response->assertStatus(200);
    }


    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
        $card = DebitCard::factory()->create();

        DebitCardTransaction::factory()->create([
            'debit_card_id' => $card->id,
            'amount'        => 100000,
            'currency_code' => 'IDR',
        ]);

        $response = $this->getJson("/api/debit-card-transactions?debit_card_id={$card->id}");

        $response->assertStatus(403);
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
        $card = DebitCard::factory()->create([
            'user_id' => $this->user->id,
        ]);

        DebitCardTransaction::factory()->create([
            'debit_card_id' => $card->id,
            'amount' => 100000,
            'currency_code' => 'IDR',
        ]);

        $response = $this->postJson("/api/debit-card-transactions", [
            'debit_card_id' => $card->id,
            'amount' => 100000,
            'currency_code' => 'IDR',
        ]);

        $response->assertStatus(201);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
        $card = DebitCard::factory()->create();

        DebitCardTransaction::factory()->create([
            'debit_card_id' => $card->id,
            'amount' => 100000,
            'currency_code' => 'IDR',
        ]);

        $response = $this->postJson("/api/debit-card-transactions", [
            'debit_card_id' => $card->id,
            'amount' => 100000,
            'currency_code' => 'IDR',
        ]);

        $response->assertStatus(403);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $card = DebitCard::factory()->create([
            'user_id' => $this->user->id,
        ]);
        $trans = DebitCardTransaction::factory()->create([
            'debit_card_id' => $card->id,
            'amount' => 100000,
            'currency_code' => 'IDR',
        ]);

        $response = $this->getJson("/api/debit-card-transactions/{$trans->id}");
        $response->assertStatus(200);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $card = DebitCard::factory()->create();
        $trans = DebitCardTransaction::factory()->create([
            'debit_card_id' => $card->id,
            'amount' => 100000,
            'currency_code' => 'IDR',
        ]);

        $response = $this->getJson("/api/debit-card-transactions/{$trans->id}");
        $response->assertStatus(403);
    }
    public function testCannotCreateTransactionWithInvalidData()
    {
        $card = DebitCard::factory()->create([
            'user_id' => $this->user->id,
        ]);
        $response = $this->postJson("/api/debit-card-transactions", [
            'debit_card_id' => $card->id,
            'amount' => 'not_a_number',
            'currency_code' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount', 'currency_code']);
    }
    public function testCurrencyMustBeValid()
    {
        $card = DebitCard::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $invalidCurrency = 'ABC'; // tidak ada dalam list

        $response = $this->postJson('/api/debit-card-transactions', [
            'debit_card_id' => $card->id,
            'amount' => 100000,
            'currency_code' => $invalidCurrency,
        ]);

        $response->assertStatus(422);
    }

    // Extra bonus for extra tests :)
}
