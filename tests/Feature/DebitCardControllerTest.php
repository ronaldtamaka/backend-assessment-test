<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
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
        DebitCard::factory()->count(3)->active()->create(['user_id' => $this->user->id]);
        DebitCard::factory()->count(3)->create();
        $this->getJson('/api/debit-cards')->assertOk()->assertJsonCount(3)->assertJsonStructure(['*' => ['id', 'number', 'type', 'expiration_date',]]);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $otherUser = User::factory()->create();
        DebitCard::factory()->count(3)->create(['user_id' => $otherUser->id]);
        $this->getJson('/api/debit-cards')->assertOk()->assertJsonCount(0);
    }

    public function testCustomerCanCreateADebitCard()
    {
        $payload = [
            'type' => 'Visa'
        ];

        $this->postJson('/api/debit-cards', $payload)
            ->assertCreated()
            ->assertJsonStructure(['id', 'number', 'type', 'expiration_date',]);

        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
            'type' => $payload['type'],
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $card = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $this->getJson("/api/debit-cards/{$card->id}")
            ->assertOk()
            ->assertJsonStructure(['id', 'number', 'type', 'expiration_date',]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $otherUser = User::factory()->create();
        $card = DebitCard::factory()->active()->create(['user_id' => $otherUser->id]);

        $this->getJson("/api/debit-cards/{$card->id}")
            ->assertForbidden();
    }

    public function testCustomerCanActivateADebitCard()
    {
        $card = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'disabled_at' => now()
        ]);

        $this->putJson("/api/debit-cards/{$card->id}", ['is_active' => true])
            ->assertOk()
            ->assertJsonFragment(['is_active' => true]);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $card->id,
            'is_active' => true
        ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        $card = DebitCard::factory()->active()->create([
            'user_id' => $this->user->id,
            'disabled_at' => null
        ]);

        $this->putJson("/api/debit-cards/{$card->id}", ['is_active' => false])
            ->assertOk()
            ->assertJsonFragment(['is_active' => false]);

        $this->assertDatabaseMissing('debit_cards', [
            'id' => $card->id,
            'disabled_at' => null,
        ]);

        $this->assertNotNull($card->fresh()->disabled_at);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $card = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $this->putJson("/api/debit-cards/{$card->id}", ['is_active' => 'boolean'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['is_active']);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        $card = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $this->deleteJson("/api/debit-cards/{$card->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('debit_cards', [
            'id' => $card->id,
        ]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        $card = DebitCard::factory()->create(['user_id' => $this->user->id]);
        DebitCardTransaction::factory()->create(['debit_card_id' => $card->id]);

        $this->deleteJson("/api/debit-cards/{$card->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $card->id,
        ]);
    }

    // Extra bonus for extra tests :)

    public function testCustomerCannotActivateOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $card = DebitCard::factory()->create([
            'user_id' => $otherUser->id,
            'disabled_at' => now(),
        ]);

        $this->putJson("/api/debit-cards/{$card->id}", ['is_active' => true])
            ->assertForbidden();

        $this->assertDatabaseHas('debit_cards', [
            'id' => $card->id,
            'disabled_at' => $card->disabled_at,
        ]);
    }

    public function testDeletedDebitCardDoesNotAppearInList()
    {
        $card = DebitCard::factory()->create(['user_id' => $this->user->id]);
        $this->deleteJson("/api/debit-cards/{$card->id}")->assertNoContent();

        $this->getJson('/api/debit-cards')
            ->assertOk()
            ->assertJsonMissing(['id' => $card->id]);
    }
}
