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
        // get /debit-card-transactions

        // Arrange
        $debitCardTransaction = DebitCardTransaction::factory()->for($this->debitCard)->create();

        // Act
        $response = $this->get('/api/debit-card-transactions?debit_card_id=' . $this->debitCard->id);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            [
                'amount',
                'currency_code',
            ],
        ]);

    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions

        // Arrange
        $otherCustomer = User::factory()->create();
        $otherCustomerDebitCard = DebitCard::factory()->create([
            'user_id' => $otherCustomer->id
        ]);
        $debitCardTransaction = DebitCardTransaction::factory()->for($otherCustomerDebitCard)->create();

        // Act
        $response = $this->get('/api/debit-card-transactions?debit_card_id=' . $otherCustomerDebitCard->id);

        // Assert
        $response->assertStatus(403);
        
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions

        // Arrange
        $data = [
            'amount' => 100,
            'currency_code' => DebitCardTransaction::CURRENCY_IDR,
            'debit_card_id' => $this->debitCard->id,
        ];

        // Act
        $response = $this->post('/api/debit-card-transactions', $data);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'amount',
            'currency_code',
        ]);
        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 100,
            'currency_code' => DebitCardTransaction::CURRENCY_IDR,
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
