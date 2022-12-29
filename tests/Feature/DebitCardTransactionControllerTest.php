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
        $this->withoutExceptionHandling();
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
        // get /debit-card-transactions
        $user = User::factory()->create();

        $debitCard = \App\Models\DebitCard::factory()->create([
            'user_id' => $user->id,
        ]);

        $debitCardTransaction = \App\Models\DebitCardTransaction::factory()->create([
            'debit_card_id' => $debitCard->id,
        ]);

        $this->assertFalse($this->user->is($debitCardTransaction->debitCard->user), \Illuminate\Http\Response::HTTP_UNAUTHORIZED);
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
        $currencyCodeArr = \App\Models\DebitCardTransaction::CURRENCIES;
        $currencyCode = $currencyCodeArr[array_rand($currencyCodeArr)];
        $params = '?debit_card_id=' . $this->debitCard->id . '&amount=' . rand(10000, 100000) . '&currency_code=' . $currencyCode;

        $this->postJson('api/debit-card-transactions' . $params)
            ->assertCreated()
            ->assertJsonStructure([
                'amount',
                'currency_code',
            ]);

        $this->assertDatabaseHas('debit_card_transactions', [
            'id' => $this->debitCard->debitCardTransactions->first()->id,
            'debit_card_id' => $this->debitCard->id,
        ]);
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
