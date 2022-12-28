<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\DebitCard;
use Laravel\Passport\Passport;
use App\Models\DebitCardTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        DebitCardTransaction::factory()->count(3)->for($this->debitCard)->create();

        $this->get('api/debit-card-transactions', [
                'debit_card_id' => $this->debitCard->id,
            ]) // get cannot send request body
            ->assertOk();

        // -- solution --
        // + this solution need to change api.php, DebitCardTransactionController.php

        // $this->get('api/debit-card-transactions/' . $this->debitCard->id)
        // ->assertOk();
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
        $this->withoutExceptionHandling();

        $newUser = User::factory()->create();
        $newUserDebitCard = DebitCard::factory()->for($newUser)->create();

        $this->get('api/debit-card-transactions', [
                'debit_card_id' => $this->newUserDebitCard->id,
            ]) // get cannot send request body
            ->assertUnauthorized()
            ->dump();

        // -- solution --
        // + this solution need to change api.php, DebitCardTransactionController.php, DebitCardTransactionShowIndexRequest.php

        // $this->get('api/debit-card-transactions/' . $this->debitCard->id)
        // ->assertOk();
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
        $this->post('/api/debit-card-transactions', [
                'debit_card_id' => 1,
                'amount' => 1000000,
                'currency_code' => 'IDR',
            ])
            ->assertJsonStructure([
                'amount',
                'currency_code',
            ])
            ->assertSuccessful();
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
        DebitCard::factory()->create();

        $this->post('/api/debit-card-transactions', [
                'debit_card_id' => 2,
                'amount' => 1000000,
                'currency_code' => 'IDR',
            ])
            ->assertForbidden();
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $debitCardTransaction = DebitCardTransaction::factory()->for($this->debitCard)->create();

        $this->get('/api/debit-card-transactions/' . $debitCardTransaction->id)
            ->assertJsonStructure([
                'amount',
                'currency_code',
            ])
            ->assertOk();
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $newUser = User::factory()->create();
        $newUserDebitCard = DebitCard::factory()->for($newUser)->create();

        $currentUserDebitCardTransaction = DebitCardTransaction::factory()->for($this->debitCard)->create();
        $newUserDebitCardTransaction = DebitCardTransaction::factory()->for($newUserDebitCard)->create();

        $this->get('/api/debit-card-transactions/' . $newUserDebitCardTransaction->id)
            // ->assertUnauthorized()
            ->assertForbidden();
    }

    // Extra bonus for extra tests :)
}
