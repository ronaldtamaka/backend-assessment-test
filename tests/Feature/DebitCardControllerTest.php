<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
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
        DebitCard::factory(5)->active()
            ->for($this->user)
            ->create();

        $response = $this->getJson('/api/debit-cards');

        $response
            ->assertOk()
            ->assertJsonCount(5)
            ->assertJsonStructure([
                '*' => ['id', 'number', 'type', 'expiration_date', 'is_active']
            ]);

        $this->assertDatabaseCount('debit_cards', 5);
        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
        ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        DebitCard::factory(5)
            ->active()
            ->create();

        $response = $this->getJson('/api/debit-cards');

        $response
            ->assertOk()
            ->assertJsonCount(0);

        $this->assertDatabaseCount('debit_cards', 5);
        $this->assertDatabaseMissing('debit_cards', [
            'user_id' => $this->user->id,
        ]);
    }

    public function testCustomerCanCreateADebitCard()
    {
        $response = $this->postJson('/api/debit-cards', [
            'type' => $type = $this->faker->creditCardType
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure(['id', 'number', 'type', 'expiration_date', 'is_active'])
            ->assertJson(['type' => $type]);

        $this->assertDatabaseCount('debit_cards', 1);
        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
            'type' => $type,
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $card = DebitCard::factory()->active()
            ->for($this->user)
            ->createOne();

        $response = $this->getJson("/api/debit-cards/{$card->id}");

        $response
            ->assertOk()
            ->assertJsonStructure(['id', 'number', 'type', 'expiration_date', 'is_active'])
            ->assertJson([
                'number' => $card->number,
                'type' => $card->type,
                'expiration_date' => $card->expiration_date->format('Y-m-d H:i:s'),
                'is_active' => $card->is_active,
            ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $card = DebitCard::factory()->active()->createOne();

        $forbidden = $this->getJson("/api/debit-cards/{$card->id}");
        $forbidden->assertForbidden();

        $notFound = $this->getJson("/api/debit-cards/invalid-debit-card");
        $notFound->assertNotFound();

        $this->assertDatabaseCount('debit_cards', 1);
        $this->assertDatabaseMissing('debit_cards', [
            'user_id' => $this->user->id,
        ]);
    }

    public function testCustomerCanActivateADebitCard()
    {
        $card = DebitCard::factory()->expired()
            ->for($this->user)
            ->createOne();

        $response = $this->putJson("/api/debit-cards/{$card->id}", [
            'is_active' => true,
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure(['id', 'number', 'type', 'expiration_date', 'is_active'])
            ->assertJson([
                'number' => $card->number,
                'type' => $card->type,
                'is_active' => true,
            ]);

        $this->assertDatabaseCount('debit_cards', 1);
        $this->assertDatabaseHas('debit_cards', [
            'disabled_at' => null,
        ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        $card = DebitCard::factory()->active()
            ->for($this->user)
            ->createOne();

        $response = $this->putJson("/api/debit-cards/{$card->id}", [
            'is_active' => false,
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure(['id', 'number', 'type', 'expiration_date', 'is_active'])
            ->assertJson([
                'number' => $card->number,
                'type' => $card->type,
                'is_active' => false,
            ]);

        $this->assertDatabaseCount('debit_cards', 1);
        $this->assertDatabaseMissing('debit_cards', [
            'disabled_at' => null,
        ]);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $card = DebitCard::factory()->active()
            ->for($this->user)
            ->createOne();

        $response = $this->putJson("/api/debit-cards/{$card->id}", [
            'is_active' => 'active',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['is_active']);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        $card = DebitCard::factory()->active()
            ->for($this->user)
            ->createOne();

        $response = $this->deleteJson("/api/debit-cards/{$card->id}");

        $response->assertNoContent();
        $this->assertDeleted('debit_cards', $card->toArray());
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        $card = DebitCard::factory()->active()
            ->for($this->user)
            ->has(DebitCardTransaction::factory(5), 'debitCardTransactions')
            ->createOne();

        $response = $this->deleteJson("/api/debit-cards/{$card->id}");

        $response->assertForbidden();
    }

    // Extra bonus for extra tests :)
    public function testCustomerCannotActivateADebitCardOfOtherCustomers()
    {
        $card = DebitCard::factory()->active()->createOne();

        $response = $this->putJson("/api/debit-cards/{$card->id}", [
            'is_active' => true,
        ]);

        $response->assertForbidden();
    }

    public function testCustomerCannotDeactivateADebitCardOfOtherCustomers()
    {
        $card = DebitCard::factory()->active()->createOne();

        $response = $this->putJson("/api/debit-cards/{$card->id}", [
            'is_active' => false,
        ]);

        $response->assertForbidden();
    }

    public function testCustomerCannotDeleteADebitCardOfOtherCustomers()
    {
        $card = DebitCard::factory()->active()->createOne();

        $response = $this->deleteJson("/api/debit-cards/{$card->id}");

        $response->assertForbidden();
    }
}
