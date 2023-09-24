<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Carbon\Carbon;


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
            'amount' => 200000,
            'currency_code' => "IDR",
        ]);


        $response = $this->get('/api/debit-card-transactions?debit_card_id='.$this->debitCard->id);

        $response->assertStatus(200);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
        $debitCardTransaction = $this->debitCard->debitCardTransactions()->create([
            'amount' => 200000,
            'currency_code' => "IDR",
        ]);


        $response = $this->get('/api/debit-card-transactions?debit_card_id=0');

        $response->assertStatus(403);
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions

        $payload = [
            "amount" => 200000,
            'currency_code' => "IDR",
            'debit_card_id' => $this->debitCard->id
        ];
        $this->json('POST', 'api/debit-card-transactions', $payload, ['Accept' => 'application/json'])
        ->assertCreated();
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
        $payload = [
            "amount" => 200000,
            'currency_code' => "coba coab",
            'debit_card_id' => $this->debitCard->id
        ];
        $this->json('POST', 'api/debit-card-transactions', $payload, ['Accept' => 'application/json'])
        ->assertStatus(422);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $debitCardTransaction = $this->debitCard->debitCardTransactions()->create([
            'amount' => 200000,
            'currency_code' => "IDR",
        ]);
        $response = $this->json('get',"api/debit-card-transactions/".$debitCardTransaction->id);

        $response->assertStatus(200);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
    }

    // Extra bonus for extra tests :)
}
