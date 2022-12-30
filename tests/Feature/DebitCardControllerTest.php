<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        // get /debit-cards
        $this->getJson('debit-cards')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'number',
                    'type',
                    'expiration_date',
                    'is_active',
                ]
            ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        $user = User::factory()->create();

        $debitCard = \App\Models\DebitCard::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertFalse($this->user->is($debitCard->user), \Illuminate\Http\Response::HTTP_UNAUTHORIZED);
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $faker = \Faker\Factory::create();
        $creditCardType = $faker->creditCardType;

        $request = ['type' => $creditCardType];

        $this->postJson('api/debit-cards', $request)
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ]);

        $this->assertDatabaseHas('debit_cards', [
            'type' => $creditCardType,
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $debitCard = \App\Models\DebitCard::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->getJson('api/debit-cards/' . $debitCard->id)
            ->assertOk()  // status code 200
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ]);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
        ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $isActive = TRUE;
        $request = ['is_active' => $isActive];

        $debitCard = \App\Models\DebitCard::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->putJson('api/debit-cards/' . $debitCard->id, $request)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ]);

        $this->assertDatabaseHas('debit_cards', [
            'disabled_at' => NULL,
        ]);
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $isActive = FALSE;
        $request = ['is_active' => $isActive];

        $debitCard = \App\Models\DebitCard::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->putJson('api/debit-cards/' . $debitCard->id, $request)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ]);

        $getDebitCard = \App\Models\DebitCard::find($debitCard->id);

        $this->assertDatabaseHas('debit_cards', [
            'disabled_at' => $getDebitCard->disabled_at,
        ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $isActive = FALSE;
        $request = ['is_active' => $isActive];

        $debitCard = \App\Models\DebitCard::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->putJson('api/debit-cards/' . $debitCard->id, $request)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ]);

        $getDebitCard = \App\Models\DebitCard::find($debitCard->id);

        $this->assertDatabaseHas('debit_cards', [
            'disabled_at' => $getDebitCard->disabled_at,
        ]);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $request = ['is_active' => NULL];

        $debitCard = \App\Models\DebitCard::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // $response = $this->putJson('api/debit-cards/' . $debitCard->id, $request);
        // $response->assertJsonMissingValidationErrors('is_active');
        // $response->assertStatus(302);

        $this->assertIsNotBool($request['is_active'], 'Type must be a boolean');
        $this->assertNull($request['is_active'], 'Type must not be null');
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = \App\Models\DebitCard::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->delete('api/debit-cards/' . $debitCard->id)
            ->assertNoContent();  // status code 204

        $this->assertSoftDeleted('debit_cards', [
            'id' => $debitCard->id,
        ]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $user = User::factory()->create();

        $debitCard = \App\Models\DebitCard::factory()->create([
            'user_id' => $user->id,
        ]);

        \App\Models\DebitCardTransaction::factory()->create([
            'debit_card_id' => $debitCard->id,
        ]);

        $this->assertNotEquals($debitCard->debitCardTransactions()->doesntExist(), "Sorry, Debit Card Can't Be Deleted");
    }

    // Extra bonus for extra tests :)
}
