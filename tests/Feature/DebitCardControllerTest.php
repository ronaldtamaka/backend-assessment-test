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
        DebitCard::factory()->create(['user_id' => $this->user->id, 'disabled_at' => now()]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200)
                ->assertJsonCount(3);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $otherUser = User::factory()->create();
        DebitCard::factory()->count(2)->active()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200)
                ->assertJsonCount(0);
    }

    public function testCustomerCanCreateADebitCard()
    {
        $data = ['type' => 'Visa'];

        $response = $this->postJson('/api/debit-cards', $data);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'id',
                    'number',
                    'type',
                    'expiration_date',
                    'is_active'
                ])
                ->assertJson([
                    'type' => 'Visa',
                    'is_active' => true
                ]);

        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
            'type' => 'Visa',
            'disabled_at' => null
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'id' => $debitCard->id,
                    'number' => $debitCard->number,
                    'type' => $debitCard->type,
                        'expiration_date' => $debitCard->expiration_date->format('Y-m-d H:i:s'),
                    'is_active' => $debitCard->is_active
                ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $otherUser = User::factory()->create();
        $debitCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(403);
    }

    public function testCustomerCanActivateADebitCard()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id, 'disabled_at' => now()]);

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", ['is_active' => true]);

        $response->assertStatus(200)
                ->assertJson([
                    'is_active' => true
                ]);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'disabled_at' => null
        ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        $debitCard = DebitCard::factory()->active()->create(['user_id' => $this->user->id]);

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", ['is_active' => false]);

        $response->assertStatus(200)
                ->assertJson([
                    'is_active' => false
                ]);

        $debitCard->refresh();
        $this->assertNotNull($debitCard->disabled_at);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", ['is_active' => 'invalid']);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['is_active']);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        $debitCard = DebitCard::factory()->active()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('debit_cards', ['id' => $debitCard->id]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        $debitCard = DebitCard::factory()->active()->create(['user_id' => $this->user->id]);
        DebitCardTransaction::factory()->create(['debit_card_id' => $debitCard->id]);

        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('debit_cards', ['id' => $debitCard->id]);
    }

    // Extra bonus for extra tests :)
}
