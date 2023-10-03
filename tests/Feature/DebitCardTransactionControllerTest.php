<?php

namespace Tests\Feature;

use App\Models\DebitCard;
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
        $user = User::factory()->create();
        $debitCard = DebitCard::factory()->create(['user_id' => $user->id]);
        DebitCardTransaction::factory(3)->create(['debit_card_id' => $debitCard->id]);

        $this->browse(function (Browser $browser) use ($user, $debitCard) {
            $browser->loginAs($user);
            $browser->visit('/api/debit-card-transactions');
            $browser->assertSee('List of Debit Card Transactions');
            $transactions = DebitCardTransaction::where('debit_card_id', $debitCard->id)->get();
            foreach ($transactions as $transaction) {
                $browser->assertSee($transaction->amount);
                $browser->assertSee($transaction->currency_code);
            }
        });
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        $user1 = User::factory()->create();

        $user2 = User::factory()->create();

        $debitCard1 = DebitCard::factory()->create(['user_id' => $user1->id]);
        $debitCard2 = DebitCard::factory()->create(['user_id' => $user2->id]);
        DebitCardTransaction::factory(3)->create(['debit_card_id' => $debitCard1->id]);
        DebitCardTransaction::factory(2)->create(['debit_card_id' => $debitCard2->id]);

        $this->browse(function (Browser $browser) use ($user1, $user2, $debitCard1, $debitCard2) {
            $browser->loginAs($user1);
            $browser->visit('/api/debit-card-transactions');

            $browser->assertSee('List of Debit Card Transactions');

            $transactions1 = DebitCardTransaction::where('debit_card_id', $debitCard1->id)->get();
            foreach ($transactions1 as $transaction) {
                $browser->assertSee($transaction->amount);
                $browser->assertSee($transaction->currency_code);
            }
            $transactions2 = DebitCardTransaction::where('debit_card_id', $debitCard2->id)->get();
            foreach ($transactions2 as $transaction) {
                $browser->assertDontSee($transaction->amount);
                $browser->assertDontSee($transaction->currency_code);
            }
        });
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {

        $user = User::factory()->create();

        $debitCard = DebitCard::factory()->create(['user_id' => $user->id]);

        $this->browse(function (Browser $browser) use ($user, $debitCard) {
            $browser->loginAs($user);
            $browser->visit('/api/create-debit-card-transaction');


            $browser->type('amount', 100);
            $browser->type('currency_code', 'USD');
            $browser->press('Create Transaction');
            $browser->assertSee('Transaction created successfully');
        });
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $debitCard1 = DebitCard::factory()->create(['user_id' => $user1->id]);

        $this->browse(function (Browser $browser) use ($user2, $debitCard1) {
            $browser->loginAs($user2);
            $browser->visit('/api/create-debit-card-transaction');


            $browser->type('debit_card_id', $debitCard1->id);
            $browser->type('amount', 100);
            $browser->type('currency_code', 'USD');
            $browser->press('Create Transaction');
            $browser->assertSee('You are not authorized to perform this action'); 
        });
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        $user = User::factory()->create();
        $debitCard = DebitCard::factory()->create(['user_id' => $user->id]);

        $transaction = DebitCardTransaction::factory()->create(['debit_card_id' => $debitCard->id]);

        $this->browse(function (Browser $browser) use ($user, $transaction) {

            $browser->loginAs($user);
            $browser->visit("/api/debit-card-transactions/{$transaction->id}");

            $browser->assertSee('Transaction Details');
            $browser->assertSee($transaction->amount);
            $browser->assertSee($transaction->currency_code);
        });
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $debitCard1 = DebitCard::factory()->create(['user_id' => $user1->id]);


        $transaction = DebitCardTransaction::factory()->create(['debit_card_id' => $debitCard1->id]);

        $this->browse(function (Browser $browser) use ($user2, $transaction) {
            $browser->loginAs($user2);
            $browser->visit("/api/debit-card-transactions/{$transaction->id}");
            $browser->assertSee('You are not authorized to perform this action');
        });
    }

    // Extra bonus for extra tests :)
}
