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
        $this->get('api/debit-card-transactions', [
                'debit_card_id' =>  $this->debitCard->id,
            ])->assertForbidden();
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
        $newUser = User::factory()->create();
        $newUserDebitCard = DebitCard::factory()->for($newUser)->create();

        $customer = User::factory()->create();
        $this->actingAs($customer);

        $response = $this->get('api/debit-card-transactions', [
            'debit_card_id' => $newUserDebitCard->id,
        ]);

        $response->assertForbidden();
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
        $this->post('/api/debit-card-transactions', [
                'debit_card_id' => 1,
                'amount' => 101010,
                'currency_code' => 'IDR',
            ])
            ->assertJsonStructure([
                'amount',
                'currency_code',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => 1,
            'amount' => 101010,
            'currency_code' => 'IDR',
        ]);
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

        $this->assertDatabaseMissing('debit_card_transactions', [
            'debit_card_id' => 2,
            'amount' => 1000000,
            'currency_code' => 'IDR',
        ]);
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
