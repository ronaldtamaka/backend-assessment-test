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
        $data = [
            'debit_card_id' =>  $this->debitCard->id,
        ];
        $response = $this->get('api/debit-card-transactions', $data);
        $response->assertStatus(403);
        $response->assertForbidden();
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        $newUser = User::factory()->create();
        $newUserDebitCard = DebitCard::factory()->for($newUser)->create();
        $data = [
            'debit_card_id' => $newUserDebitCard->id,
        ];
        $response = $this->get('api/debit-card-transactions', $data);

        $response->assertStatus(403);
        $response->assertForbidden();
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $data = [
            'debit_card_id' => 1,
            'amount' => 101010,
            'currency_code' => 'IDR',
        ];
        $response = $this->post('/api/debit-card-transactions', $data);
        $response->assertStatus(201);
        $response->assertSuccessful();
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        DebitCard::factory()->create();
        $data = [
            'debit_card_id' => 2,
            'amount' => 1000000,
            'currency_code' => 'IDR',
        ];

        $response = $this->post('/api/debit-card-transactions', $data);
        $response->assertStatus(403);
        $response->assertForbidden();
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        $debitCardTransaction = DebitCardTransaction::factory()->for($this->debitCard)->create();

        $response = $this->get('/api/debit-card-transactions/' . $debitCardTransaction->id);
        $response->assertStatus(200);
        $response->assertOk();
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        $newUser = User::factory()->create();
        $newUserDebitCard = DebitCard::factory()->for($newUser)->create();

        DebitCardTransaction::factory()->for($this->debitCard)->create();
        $newUserDebitCardTransaction = DebitCardTransaction::factory()->for($newUserDebitCard)->create();

        $response = $this->get('/api/debit-card-transactions/' . $newUserDebitCardTransaction->id);
        $response->assertStatus(403);
        $response->assertForbidden();
    }

    // Extra bonus for extra tests :)
}
