<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

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

    /**
     *
     */
    public function testCustomerCanSeeAListOfDebitCards()
    {
        DebitCard::create([
            'user_id' => $this->user->id,
            'number' => 123,
            'type' => "BCA",
            'expiration_date' => '2025-12-31 15:29:29',
            'is_active' => true
        ]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    /**
     *
     */
    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $user = User::create([
            'name' => 'fail',
            'email' => 'fail@mail.com',
            'password' => bcrypt('password')
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson("api/debit-cards");

        // TODO
        $response->assertStatus(200);
    }

    /**
     *
     */
    public function testCustomerCanCreateADebitCard()
    {
        $response = $this->postJson('api/debit-cards', ['type' => 'card-new']);

        $response->assertStatus(201)
            ->assertJson([
                'type' => 'card-new',
            ]);
    }

    /**
     *
     */
    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id, 'type' => 'debit-debit']);

        $response = $this->getJson("api/debit-cards/{$debitCard->id}");

        $response->assertJsonFragment(['type' => 'debit-debit']);

        $response->assertStatus(200);
    }

    /**
     *
     */
    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $debitCard = DebitCard::factory()->create(); // DebitCard for another user

        $response = $this->getJson("api/debit-cards/{$debitCard->id}");

        $response->assertStatus(403);
    }

    /**
     *
     */
    public function testCustomerCanActivateADebitCard()
    {
        $debitCard = DebitCard::create([
            'user_id' => $this->user->id,
            'disabled_at' => now(),
            'number' => 123,
            'type' => "BCA",
            'expiration_date' => now()
        ]);

        $response = $this->putJson("api/debit-cards/{$debitCard->id}", ['is_active' => true]);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'number' => (int) $debitCard->number,
            'type' => $debitCard->type,
            'is_active' => true,
        ]);
    }

    /**
     *
     */
    public function testCustomerCanDeactivateADebitCard()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->putJson("api/debit-cards/{$debitCard->id}", ['is_active' => false]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'number' => (int) $debitCard->number,
                'type' => $debitCard->type,
                'is_active' => false,
            ]);
    }

    /**
     *
     */
    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->putJson("api/debit-cards/{$debitCard->id}", ['is_active' => 'invalid_value']);

        $response->assertStatus(422);
    }

    /**
     *
     */
    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("api/debit-cards/{$debitCard->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('debit_cards', ['id' => $debitCard->id]);
    }

    /**
     *
     */
    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);
        $transaction = DebitCardTransaction::factory()->create(['debit_card_id' => $debitCard->id]);

        $response = $this->deleteJson("api/debit-cards/{$debitCard->id}");

        $response->assertStatus(403);
    }

    // Extra bonus for extra tests :)
}
