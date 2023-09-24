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
        $debitCard = DebitCard::create([
            'type' => "Gold",
            'user_id'=> $this->user->id,
            'number' => rand(1000000000000000, 9999999999999999),
            'expiration_date' => Carbon::now()->addYear(),
        ]);
        $debitCardTransaction = $debitCard->debitCardTransactions()->create([
            'amount' => 200000,
            'currency_code' => "coba coba",
        ]);


        $response = $this->get('/api/debit-card-transactions?debit_card_id='.$debitCard->id);

        $response->assertStatus(200);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
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
