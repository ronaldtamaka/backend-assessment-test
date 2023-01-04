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
        $debitCardTransaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id
        ]);

        $response = $this->get('/api/debit-card-transactions?debit_card_id=' . $this->debitCard->id);

        $response->assertOk();

        $response->assertJsonStructure([
            '*' => [
                'amount',
                'currency_code',
            ],
        ]);

        $response->assertJsonFragment([
            'amount' => (string) $debitCardTransaction->amount,
            'currency_code' => $debitCardTransaction->currency_code,
        ]);

        $this->assertDatabaseHas('debit_card_transactions', [
            'id' => $debitCardTransaction->id,
            'amount' => $debitCardTransaction->amount,
            'currency_code' => $debitCardTransaction->currency_code,
        ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        $debitCardTransaction = DebitCardTransaction::factory()->create();

        $response = $this->get('/api/debit-card-transactions?debit_card_id=' . $this->debitCard->id);

        $response->assertOk();

        $response->assertJsonStructure([
            '*' => [
                'amount',
                'currency_code',
            ],
        ]);

        $response->assertJsonMissing([
            'amount' => (string) $debitCardTransaction->amount,
            'currency_code' => $debitCardTransaction->currency_code,
        ]);

        $this->assertDatabaseHas('debit_card_transactions', [
            'id' => $debitCardTransaction->id,
            'amount' => $debitCardTransaction->amount,
            'currency_code' => $debitCardTransaction->currency_code,
        ]);
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
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
