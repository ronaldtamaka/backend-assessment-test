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
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->postJson('/api/debit-card-transactions', [
            'debit_card_id' => $debitCard->id,
            'amount' => 10000,
            'currency_code'  => 'IDR'
        ]);

        $response->assertStatus(201);

        $response->assertJson([
            'amount' => 10000,
            'currency_code' => 'IDR'
        ]);
    }

    /**
     *
     */
    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $user = User::create([
            'name' => 'fail',
            'email' => 'fail@mail.com',
            'password' => bcrypt('password')
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/debit-card-transactions', [
                'debit_card_id' => $debitCard->id,
                'amount' => 10000,
                'currency_code'  => 'IDR'
            ]);

        $response->assertStatus(403);
    }

    /**
     *
     */
    public function testCustomerCanSeeADebitCardTransaction()
    {
        $debitCradTransaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id
        ]);

        $response = $this->getJson("/api/debit-card-transactions/{$debitCradTransaction->id}");
        $response->assertStatus(200);
    }

    /**
     *
     */
    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        $debitCardTransaction = DebitCardTransaction::factory()->create(['debit_card_id' => $this->debitCard->id]);

        $debitCardTransaction->dump();
        $user = User::create([
            'name' => 'fail',
            'email' => 'fail@mail.com',
            'password' => bcrypt('password')
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/debit-card-transactions/{$debitCardTransaction->id}");

        $response->assertStatus(403);
    }

    // Extra bonus for extra tests :)
}
