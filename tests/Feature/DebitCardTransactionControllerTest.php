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
        $debitCardTransaction = $this->debitCard->debitCardTransactions()->create([
            'amount' => 1000000,
            'currency_code' => DebitCardTransaction::CURRENCY_IDR,
        ]);


        $res = $this->get('/api/debit-card-transactions?debit_card_id=' . $this->debitCard->id);
        $res->assertOk();
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
        $newUser = User::factory()->create();
        $debitCard = DebitCard::factory()->create([
            'user_id' => $newUser->id
        ]);
        $debitCard->debitCardTransactions()->create([
            'amount' => 1000000,
            'currency_code' => DebitCardTransaction::CURRENCY_IDR,
        ]);

        $res = $this->get('/api/debit-card-transactions?debit_card_id=' . $debitCard->id);
        $res->assertForbidden();
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
        $data = [
            'debit_card_id' => 1,
            'amount' => 101010,
            'currency_code' => DebitCardTransaction::CURRENCY_IDR,
        ];
        $res = $this->post('/api/debit-card-transactions', $data);
        $res->assertJsonStructure([
            'amount',
            'currency_code',
        ]);
        $res->assertSuccessful();

        $this->assertDatabaseHas('debit_card_transactions', $data);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
        $newUser = User::factory()->create();
        $debitCard = DebitCard::factory()->create([
            'user_id' => $newUser->id
        ]);

        $data = [
            'debit_card_id' => $debitCard->id,
            'amount' => 1000000,
            'currency_code' => DebitCardTransaction::CURRENCY_IDR,
        ];
        $res = $this->post('/api/debit-card-transactions', $data);
        $res->assertForbidden();

        $this->assertDatabaseMissing('debit_card_transactions', $data);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $debitCardTransaction = DebitCardTransaction::factory()->for($this->debitCard)->create();

        $res = $this->get('/api/debit-card-transactions/' . $debitCardTransaction->id);
        $res->assertJsonStructure([
            'amount',
            'currency_code',
        ]);
        $res->assertOk();
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $newUser = User::factory()->create();
        $debitCard = DebitCard::factory()->create([
            'user_id' => $newUser->id
        ]);
        $debitCard->debitCardTransactions()->create([
            'amount' => 1000000,
            'currency_code' => DebitCardTransaction::CURRENCY_IDR,
        ]);

        $res = $this->get('/api/debit-card-transactions/' . $debitCard->debitCardTransactions()->first()->id);
        $res->assertForbidden();
    }

    // Extra bonus for extra tests :)
}
