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
        // get /debit-card-transactions
        \App\Models\DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id,
        ]);

        $params = '?debit_card_id=' . $this->debitCard->id;

        $response = $this->getJson('api/debit-card-transactions' . $params)
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'amount',
                    'currency_code',
                ]
            ]);

        $this->assertGreaterThan(0, count($response->json()));
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {

        \App\Models\DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id,
        ]);

        $user2 = User::factory()->create();
        $debitCard2 = DebitCard::factory()->create([
            'user_id' => $user2->id
        ]);

        $params = '?debit_card_id=' . $debitCard2->id;

        $this->getJson('api/debit-card-transactions' . $params)
            ->assertStatus(403);
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
        $response = $this->postJson('api/debit-card-transactions', ['debit_card_id' => $this->debitCard->id, 'amount' => '100000', 'currency_code' => 'IDR']);
        $response->assertStatus(201);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
        $user2 = User::factory()->create();
        $debitCard2 = DebitCard::factory()->create([
            'user_id' => $user2->id
        ]);

        $response = $this->postJson('api/debit-card-transactions', ['debit_card_id' => $debitCard2->id, 'amount' => '100000', 'currency_code' => 'IDR']);
        $response->assertStatus(403);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $debitTrans = \App\Models\DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id,
            'amount' => '100000',
            'currency_code' => 'IDR'
        ]);

        $this->getJson('api/debit-card-transactions/' . $debitTrans->id)
            ->assertOk();
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}

        $user2 = User::factory()->create();
        $debitCard2 = DebitCard::factory()->create([
            'user_id' => $user2->id
        ]);

        $debitTrans2 = \App\Models\DebitCardTransaction::factory()->create([
            'debit_card_id' => $debitCard2->id,
            'amount' => '150000',
            'currency_code' => 'IDR'
        ]);

        $this->getJson('api/debit-card-transactions/' . $debitTrans2->id)
            ->assertStatus(403);
    }

    // Extra bonus for extra tests :)
}
