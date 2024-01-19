<?php

namespace Tests\Feature;

use App\Http\Requests\DebitCardTransactionShowIndexRequest;
use App\Http\Resources\DebitCardTransactionResource;
use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response as HttpResponse;


class DebitCardTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected DebitCard $debitCard;

    protected User $userOther;
    protected DebitCardTransaction $debitCardTransaction;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);
        $this->userOther = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCardTransactions()
    {
        // get /debit-card-transactions

        $debitCard = $this->user->debitCards->first();
        DebitCardTransaction::factory(5)->create([
            "debit_card_id" => $debitCard->id
        ]);
        $response = $this->get('api/debit-card-transactions?debit_card_id='. $debitCard->id);
        $response->assertStatus(\Illuminate\Http\Response::HTTP_OK)

            ->assertJsonStructure([
                '*' => [
                    'amount',
                    'currency_code'
                ]
            ]);
        $this->assertDatabaseHas('debit_cards', ['id' => $debitCard->id, 'user_id' => $this->user->id]);
        $this->assertDatabaseHas('debit_card_transactions', ['debit_card_id' => $debitCard->id]);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
        $debitCard = $this->user->debitCards->first();
        DebitCardTransaction::factory(5)->create([
            'debit_card_id' => $debitCard->id
        ]);
        Passport::actingAs($this->userOther);
        $response = $this->get('api/debit-card-transactions?debit_card_id='. $debitCard->id);
        $responseJson = json_decode($response->content(), true);
        $this->assertEmpty(
            $responseJson,
            'customer cant see a list of debit card transactions of other customer'
        );
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
        $debitCard = $this->user->debitCards->first();
        $params = [
            'debit_card_id' => $debitCard->id,
            'amount' => 30125558,
            'currency_code' => DebitCardTransaction::CURRENCY_SGD
        ];
        $response = $this->post('api/debit-card-transactions', $params);
        $response->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonStructure([
                'amount',
                'currency_code'
            ]);
        $this->assertDatabaseHas('debit_card_transactions', ['debit_card_id' => $debitCard->id, 'amount' => 30125558, 'currency_code' => DebitCardTransaction::CURRENCY_SGD]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
        $debitCard = $this->user->debitCards->first();
        Passport::actingAs($this->userOther);
        $params = [
            'debit_card_id' => $debitCard->id,
            'amount' => 56785,
            'currency_code' => DebitCardTransaction::CURRENCY_SGD
        ];
        $response = $this->post('api/debit-card-transactions', $params);
        $response->assertStatus(HttpResponse::HTTP_FORBIDDEN);
        $this->assertDatabaseMissing('debit_card_transactions', ['debit_card_id' => $debitCard->id, 'amount' => 56785, 'currency_code' => DebitCardTransaction::CURRENCY_SGD]);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $debitCard = $this->user->debitCards->first();

        DebitCardTransaction::factory(5)->create([
            "debit_card_id" => $debitCard->id
        ]);

        $debitCardTransaction = $debitCard->debitCardTransactions->first();
        $response = $this->get('api/debit-card-transactions/'. $debitCardTransaction->id);
        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonStructure([
                'amount',
                'currency_code'
            ]);

        $this->assertDatabaseHas('debit_card_transactions', ['debit_card_id' => $debitCard->id]);

    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $debitCard = $this->user->debitCards->first();
        DebitCardTransaction::factory(5)->create([
            "debit_card_id" => $debitCard->id
        ]);
        Passport::actingAs($this->userOther);
        $debitCardTransaction = $debitCard->debitCardTransactions->first();
        $response = $this->get('api/debit-card-transactions/'. $debitCardTransaction->id);
        $response->assertStatus(HttpResponse::HTTP_FORBIDDEN);
        $this->assertDatabaseMissing('debit_cards', ['id' => $debitCard->id, 'user_id' => $this->userOther->id]);
    }

    // Extra bonus for extra tests :)
}
