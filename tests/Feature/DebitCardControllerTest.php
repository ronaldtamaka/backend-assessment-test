<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        DebitCard::factory(5)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/debit-cards');
        $response->assertStatus(200);

        $response->assertJsonStructure([
            '*' => [
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ],
        ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards

        $ownDebitCards = DebitCard::factory(3)->create([
            'user_id' => $this->user->id,
        ]);

        $otherDebitCards = DebitCard::factory(2)->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->getJson('/api/debit-cards');
        
        $response->assertStatus(200);

        // // jumlah nya harus sesuai dengan debit card miliknya (3)
        $response->assertJsonCount($ownDebitCards->count());

        // // memastikan id dari setiap debit card miliknya ada di response API
        $response->assertJsonFragment([
            'id' => $ownDebitCards[0]->id,
        ]);

        $response->assertJsonFragment([
            'id' => $ownDebitCards[1]->id,
        ]);

        $response->assertJsonFragment([
            'id' => $ownDebitCards[2]->id,
        ]);

        // yang bukan miliknya tidak boleh muncul
        foreach ($otherDebitCards as $otherCard) {
            $response->assertJsonMissing([
                'id' => $otherCard->id,
            ]);
        }
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards

        $response = $this->actingAs($this->user, 'api')->postJson('/api/debit-cards', [
            'type' => 'visa',
        ]);

        // make sure response 201 (created)
        $response->assertCreated();

        // make sure data nya sudah masuk
        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
            'type' => 'visa',
        ]);

        // make sure response body sudah sesuai
        $response->assertJsonStructure([
            'id',
            'type',
            'number',
            'expiration_date',
            'is_active'
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}

        $customer = User::factory()->create();

        $debitCard = DebitCard::factory()->create([
            'user_id' => $customer->id
        ]);

        $response = $this->actingAs($customer, 'api')
            ->getJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(200);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}

        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();

        $debitCard = DebitCard::factory()->create([
            'user_id' => $otherCustomer->id
        ]);

        $response = $this->actingAs($customer, 'api')
            ->getJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.'
        ]);
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}

        $customer = User::factory()->create();

        $debitCard = DebitCard::factory()->create([
            'user_id' => $customer->id,
        ]);

        $response = $this->actingAs($customer, 'api')
            ->putJson("api/debit-cards/{$debitCard->id}", [
                'is_active' => true
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'is_active' => true
        ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $customer = User::factory()->create();

        $debitCard = DebitCard::factory()->create([
            'user_id' => $customer->id,
        ]);

        $response = $this->actingAs($customer, 'api')
            ->putJson("api/debit-cards/{$debitCard->id}", [
                'is_active' => false
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'is_active' => false
        ]);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}

        $customer = User::factory()->create();

        $debitCard = DebitCard::factory()->create([
            'user_id' => $customer->id,
        ]);

        // test set a string
        $response = $this->actingAs($customer, 'api')
            ->putJson("api/debit-cards/{$debitCard->id}", [
                'is_active' => 'lorem-ipsum-hehehe'
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['is_active']);
        $response->assertJsonFragment([
            'The is active field must be true or false.'
        ]);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}

        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("api/debit-cards/{$debitCard->id}");

        $response->assertStatus(204);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // make a transaction
        $debitCard->debitCardTransactions()->create([
            'amount' => 10000,
            'currency_code' => 'IDR',
        ]);

        $response = $this->deleteJson("api/debit-cards/{$debitCard->id}");

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.'
        ]);
    }

    // Extra bonus for extra tests :)
    
    public function testCustomerCannotDeleteOtherCustomerDebitCard()
    {
        $secondCustomer = User::factory()->create();

        $firstCustomerDebitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $secondCustomerDebitCard = DebitCard::factory()->create([
            'user_id' => $secondCustomer->id,
        ]);

        $response = $this->deleteJson("api/debit-cards/{$secondCustomerDebitCard->id}");

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.'
        ]);
    }

    public function testCustomerCannotEditOtherCustomerDebitCard()
    {
        $firstCustomer = User::factory()->create();
        $secondCustomer = User::factory()->create();

        $firstCustomerDebitCard = DebitCard::factory()->create([
            'user_id' => $firstCustomer->id,
            'number' => '1234-5678-9012-3456'
        ]);

        $response = $this->actingAs($secondCustomer, 'api')
            ->putJson("api/debit-cards/{$firstCustomerDebitCard->id}", [
                'number' => '9876-5432-1098-7654'
            ]);

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.'
        ]);
    }

    public function testCustomerCannotActiveOtherCustomerDebitCard()
    {
        $firstCustomer = User::factory()->create();
        $secondCustomer = User::factory()->create();

        $firstCustomerDebitCard = DebitCard::factory()->create([
            'user_id' => $firstCustomer->id,
        ]);

        $response = $this->actingAs($secondCustomer, 'api')
            ->putJson("api/debit-cards/{$firstCustomerDebitCard->id}", [
                'is_active' => true
            ]);

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.'
        ]);
    }

    public function testCustomerCannotDeactivateOtherCustomerDebitCard()
    {
        $firstCustomer = User::factory()->create();
        $secondCustomer = User::factory()->create();

        $firstCustomerDebitCard = DebitCard::factory()->create([
            'user_id' => $firstCustomer->id,
        ]);

        $response = $this->actingAs($secondCustomer, 'api')
            ->putJson("api/debit-cards/{$firstCustomerDebitCard->id}", [
                'is_active' => false
            ]);

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.'
        ]);
    }
}
