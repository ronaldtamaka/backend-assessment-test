<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Feature\Helper\DebitCardTestHelper;
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
        DebitCardTestHelper::createTransaction($this, $this->debitCard->id);

        $url = '/api/debit-card-transactions/?debit_card_id='.$this->debitCard->id;
        $response = $this->get($url);
        $response->assertStatus(200);

        // assert that response not empty
        $this->assertNotEmpty($response->json());

        // assert that response have correct structure
        $response->assertJsonStructure([
            '*' => [
                'amount',
                'currency_code'
            ]
        ]);  
        
        // assert that response have correct data type
        $data = $response->json();
        $this->assertIsString($data[0]['amount']);
        $this->assertIsString($data[0]['currency_code']);
        
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        $secondUser = User::factory()->create();
        $secondDebitCard = DebitCard::factory()->create([
            'user_id' => $secondUser->id
        ]);
        DebitCardTestHelper::createTransaction($this->actingAs($secondUser), $secondDebitCard->id);

        $url = '/api/debit-card-transactions/?debit_card_id='.$secondDebitCard->id;
        $response = $this->actingAs($this->user)->get($url);
        $response->assertStatus(403);
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $response = DebitCardTestHelper::createTransaction($this, $this->debitCard->id);
        $response->assertStatus(201);
        $response->assertJsonStructure(['amount', 'currency_code']);
        
        $jsonResponse = $response->json();

        // assert that database has created debit card data
        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => $jsonResponse['amount'],
            'currency_code' => $jsonResponse['currency_code'],
        ]);

        // assert that response have correct data type
        $this->assertIsInt($jsonResponse['amount']);
        $this->assertIsString($jsonResponse['currency_code']);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        $secondUser = User::factory()->create();
        $secondDebitCard = DebitCard::factory()->create([
            'user_id' => $secondUser->id
        ]);
        $response = DebitCardTestHelper::createTransaction($this->actingAs($this->user), $secondDebitCard->id);
        $response->assertStatus(403);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        $debitCardTransaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id,
            'amount' => 100,
            'currency_code' => 'IDR',
        ]);

        $url = '/api/debit-card-transactions/'.$debitCardTransaction->id;
        $response = $this->get($url);
        $response->assertStatus(200);

        // assert that response have correct structure
        $response->assertJsonStructure([
            'amount',
            'currency_code'
        ]);

        // assert that response have correct data
        $this->assertEquals($debitCardTransaction->amount, $response->json()['amount']);
        $this->assertEquals($debitCardTransaction->currency_code, $response->json()['currency_code']);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        $secondUser = User::factory()->create();
        
        $debitCardTransaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id,
            'amount' => 100,
            'currency_code' => 'IDR',
        ]);

        $url = '/api/debit-card-transactions/'.$debitCardTransaction->id;
        $response = $this->actingAs($secondUser)->get($url);
        $response->assertStatus(403);
    }

    // Extra bonus for extra tests :)
}
