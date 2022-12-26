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
        $response = $this->get('/debit-card-transactions')
                ->assertJsonStructure([
                    'status',
                    'result' => [
                        'debit_card_id',
                        'amount',
                        'currency_code',
                    ],
                ]);

        $response->assertOk();
        
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
        $response = $this->get('/debit-card-transactions');
                
        $response->assertTrue($this->debitCards->has('debitCardTransactions'));
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
        $response = $this->post('/debit-card-transactions', [
            'debit_card_id' => 1,
            'amount' => 2000,
            'currency_code' => 'IDR',
        ]);

        $newDebitCardTransaction = (array)json_decode($response->response->content());

        $response->assertArrayHasKey('debit_card_id', $newDebitCardTransaction);
        $response->assertCount(
            1,
            $this->debitCards->debitCardTransaction->latest()->count(), "1 data has been added"
        );
        $response->assertEquals($newDebitCardTransaction['debit_card_id'], $this->debitCards->debitCardTransaction->get());
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
        $response = $this->post('/debit-card-transactions', [
            'debit_card_id' => 1,
            'amount' => 2000,
            'currency_code' => 'IDR',
        ]);

        $newDebitCardTransaction = $response->request->getContent();
        $getDebitCard = $this->debitCard->where(['id'=> $newDebitCardTransaction['debit_card_id']])->get();

        $response->assertValid(['debit_card_id']);
        $response->assertEquals($this->user->id, $getDebitCard['user_id']);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $debitCardTransactionId = 1;
        $response = $this->get('/debit-card-transactions', [
            'id' => $debitCardTransactionId,
        ])->assertJsonStructure([
            'status',
            'result' => [
                'debit_card_id',
                'amount',
                'currency_code',
            ],
        ]);
        
        $response->assertSuccessful();
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $debitCardTransactionId = 1;
        $response = $this->get('/debit-card-transactions', [
            'id' => $debitCardTransactionId,
        ]);
        
        $debitCardTransaction = (array)json_decode($response->response->content());
        $getDebitCard = $this->debitCard->where(['id'=> $newDebitCardTransaction['debit_card_id']])->get();

        $response->assertSuccessful();
        $response->assertEquals($this->user->id, $getDebitCard['user_id']);
    }

    // Extra bonus for extra tests :)
}
