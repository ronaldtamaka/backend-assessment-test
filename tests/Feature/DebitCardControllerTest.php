<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
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
        DebitCard::factory()->count(2)->for($this->user)->create(['disabled_at' => null]);

        $response = $this->getJson('/api/debit-cards');
        $response->assertOk();
        $this->assertCount(2, $response->json());
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $otherUser = User::factory()->create();
        DebitCard::factory()->count(3)->for($otherUser)->create();

        $response = $this->getJson('/api/debit-cards');

        $response->assertOk(); // endpoint valid
        $this->assertCount(0, $response->json());
    }

    public function testCustomerCanCreateADebitCard()
    {
        $payload = [
            'type' => 'type',
        ];

        $response = $this->postJson('/api/debit-cards', $payload);

        $response->assertCreated()
                ->assertJsonStructure([
                    'id',
                    'number',
                    'type',
                    'expiration_date',
                    'is_active',
                ]);

        $number = $response->json('number');

        $this->assertDatabaseHas('debit_cards', [
            'number' => $number,
            'user_id' => $this->user->id,
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $card = DebitCard::factory()->for($this->user)->create();

        $response = $this->getJson("/api/debit-cards/{$card->id}");

        $response->assertOk()
                 ->assertJsonFragment(['id' => $card->id]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $otherUser = User::factory()->create();
        $card = DebitCard::factory()->for($otherUser)->create();

        $response = $this->getJson("/api/debit-cards/{$card->id}");

        $response->assertForbidden();
    }

    public function testCustomerCanActivateADebitCard()
    {
        $card = DebitCard::factory()->for($this->user)->create([
            'disabled_at' => now(),
        ]);

        $response = $this->putJson("/api/debit-cards/{$card->id}", [
            'is_active' => true,
        ]);

        $response->assertOk()
                ->assertJsonFragment(['id' => $card->id]);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $card->id,
            'disabled_at' => null, 
        ]);
    }

   public function testCustomerCanDeactivateADebitCard()
    {
        $card = DebitCard::factory()->for($this->user)->create([
            'disabled_at' => null,
        ]);

        $response = $this->putJson("/api/debit-cards/{$card->id}", [
            'is_active' => false,
        ]);

        $response->assertOk();

        $this->assertDatabaseMissing('debit_cards', [
            'id' => $card->id,
            'disabled_at' => null,
        ]);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $card = DebitCard::factory()->for($this->user)->create();

        $response = $this->putJson("/api/debit-cards/{$card->id}", [
            'is_active' => '', 
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['is_active']);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        $card = DebitCard::factory()->for($this->user)->create([
            'disabled_at' => null
        ]);

        $response = $this->deleteJson("/api/debit-cards/{$card->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('debit_cards', ['id' => $card->id]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        $card = DebitCard::factory()->for($this->user)->create([
            'disabled_at' => null
        ]);
        DebitCardTransaction::factory()->for($card)->create();

        $response = $this->deleteJson("/api/debit-cards/{$card->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('debit_cards', ['id' => $card->id]);
    }

    public function testCustomerCannotAccessDebitCardOfOthersEvenOnUpdate()
    {
        $otherUser = User::factory()->create();
        $card = DebitCard::factory()->for($otherUser)->create([
            'disabled_at' => null
        ]);

        $response = $this->putJson("/api/debit-cards/{$card->id}", [
            'is_active' => false,
        ]);

        $response->assertForbidden();
    }
}