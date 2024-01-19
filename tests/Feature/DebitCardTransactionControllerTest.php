<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
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
        DebitCardTransaction::factory()->count(3)->for($this->debitCard)->create();

        $this->get('api/debit-card-transactions', [
            'debit_card_id' => $this->debitCard->id,
        ])
        ->assertOk();
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        $this->withoutExceptionHandling();

        $newUser = User::factory()->create();
        $newUserDebitCard = DebitCard::factory()->for($newUser)->create();

        $this->get('api/debit-card-transactions', [
            'debit_card_id' => $newUserDebitCard->id,
        ]) // get cannot send request body
        ->assertUnauthorized()
        ->dump();
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $this->post('/api/debit-card-transactions', [
            'debit_card_id' => 1,
            'amount' => 123123,
            'currency_code' => 'IDR',
        ])
            ->assertJsonStructure([
                'amount',
                'currency_code',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => 1,
            'amount' => 123123,
            'currency_code' => 'IDR',
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        DebitCard::factory()->create();
        $randomNumber = mt_rand(1000000,9999999);
        $this->post('/api/debit-card-transactions', [
            'debit_card_id' => 2,
            'amount' => $randomNumber,
            'currency_code' => 'IDR',
        ])
            ->assertForbidden();

        $this->assertDatabaseMissing('debit_card_transactions', [
            'debit_card_id' => 2,
            'amount' => $randomNumber,
            'currency_code' => 'IDR',
        ]);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
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
        $newUser = User::factory()->create();
        $newUserDebitCard = DebitCard::factory()->for($newUser)->create();

        $currentUserDebitCardTransaction = DebitCardTransaction::factory()->for($this->debitCard)->create();
        $newUserDebitCardTransaction = DebitCardTransaction::factory()->for($newUserDebitCard)->create();

        $this->get('/api/debit-card-transactions/' . $newUserDebitCardTransaction->id)
            ->assertForbidden();
            // ->assertUnauthorized()
    }

    // Extra bonus for extra tests :)
}
