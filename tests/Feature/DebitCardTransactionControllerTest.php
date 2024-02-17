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

    /**
     *
     */
    public function testCustomerCanSeeAListOfDebitCardTransactions()
    {
        DebitCardTransaction::factory()->create(['debit_card_id' => $this->debitCard->id]);

        $response = $this->getJson('api/debit-card-transactions?debit_card_id=' . $this->debitCard->id);

        $response->assertStatus(200);
    }

    /**
     *
     */
    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        $user = User::create([
            'name' => 'fail',
            'email' => 'fail@mail.com',
            'password' => bcrypt('password')
        ]);

        $response = $this->actingAs($user, 'api')
            ->get('api/debit-card-transactions?debit_card_id=' . $this->debitCard->id);

        $responseJson = json_decode($response->content(), true);
        $this->assertEmpty(
            $responseJson,
            'customer cant see a list of debit card transactions of other customer'
        );
    }

    /**
     *
     */
    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
    }

    // public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    // {
    //     // post /debit-card-transactions
    // }

    // public function testCustomerCanSeeADebitCardTransaction()
    // {
    //     // get /debit-card-transactions/{debitCardTransaction}
    // }

    // public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    // {
    //     // get /debit-card-transactions/{debitCardTransaction}
    // }

    // Extra bonus for extra tests :)
}
