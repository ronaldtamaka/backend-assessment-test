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
        $this->debitCard = DebitCard::factory()->for($this->user)->create();

        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCardTransactions()
    {
        // get /debit-card-transactions
        // DebitCardTransaction::factory()->count(3)->for($this->debitCard)->create();
        // $this->get('/api/debit-card-transactions/', [
        //     'debit_card_id' => $this->debitCard->id
        // ])->assertOk();
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
        DebitCardTransaction::factory()->count(3)->for($this->debitCard)->create();
        $customer = Passport::actingAs(User::factory()->create());
        
        $this->actingAs($customer)->get('/api/debit-card-transactions/', [
            'debit_card_id' => $this->debitCard->id
        ])->assertForbidden();
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $res = $this->actingAs($this->user)->post('/api/debit-card-transactions/', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 100,
            'currency_code' => DebitCardTransaction::CURRENCY_IDR,
        ])->assertCreated();

        $json = $res->decodeResponseJson()->json();

        $this->assertDatabaseHas('debit_card_transactions', [
            'amount' => $json['amount'],
            'currency_code' => $json['currency_code'],
        ]);
    }

    public function testInvalidCustomerCanCreateADebitCardTransaction()
    {
        $this->actingAs($this->user)->post('/api/debit-card-transactions/', [
            'debit_card_id' => $this->debitCard->id,
            'currency_code' => DebitCardTransaction::CURRENCY_IDR,
        ])->assertSessionHasErrors(['amount']);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
        $user = Passport::actingAs(User::factory()->create());

        $this->actingAs($user)->post('/api/debit-card-transactions/', [
            'debit_card_id' => $this->debitCard->id,
            'currency_code' => DebitCardTransaction::CURRENCY_IDR,
        ])->assertForbidden();
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $d = DebitCardTransaction::factory()->for($this->debitCard)->create();
        $this->get('/api/debit-card-transactions/'. $d->id)->assertOk();
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        DebitCardTransaction::factory()->count(3)->for($this->debitCard)->create();

        $customer = Passport::actingAs(User::factory()->create());
        $this->actingAs($customer);

        $this->get('/api/debit-card-transactions/'. $this->debitCard->id)->assertForbidden();
    }

    // Extra bonus for extra tests :)
}
