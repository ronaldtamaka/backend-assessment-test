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
        DebitCardTransaction::factory()->count(3)->create([
            'debit_card_id' => $this->debitCard->id
        ]);

        $response = $this->json('GET', '/debit-card-transactions/list');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        $otherDebitCard = DebitCard::factory()->create();

        DebitCardTransaction::factory()->count(3)->create([
            'debit_card_id' => $otherDebitCard->id
        ]);

        $response = $this->json('GET', '/debit-card-transactions/list');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $data = [
            'amount' => 100,
            'currency_code' => 'USD',
        ];

        $response = $this->json('POST', '/debit-card-transactions/create', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('debit_card_transactions', $data);

        $debitCardTransaction = DebitCardTransaction::latest()->first();
        $response->assertJsonPath('data.id', $debitCardTransaction->id);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        $otherDebitCard = DebitCard::factory()->create();

        $data = [
            'debit_card_id' => $otherDebitCard->id,
            'amount' => 100,
            'currency_code' => 'USD',
        ];

        $response = $this->json('POST', '/debit-card-transactions/create', $data);

        $response->assertStatus(403);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        $debitCardTransaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id
        ]);

        $response = $this->json('GET', '/debit-card-transactions/' . $debitCardTransaction->id);

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $debitCardTransaction->id);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        $otherDebitCard = DebitCard::factory()->create();
        $debitCardTransaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $otherDebitCard->id
        ]);

    $response = $this->json('GET', '/debit-card-transactions/' . $debitCardTransaction->id);

        $response->assertStatus(403);
    }
}
