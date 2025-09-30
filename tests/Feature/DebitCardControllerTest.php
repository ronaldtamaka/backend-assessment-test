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
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        // Create debit cards for the authenticated user
        $debitCard1 = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'Visa',
            'disabled_at' => null
        ]);
        $debitCard2 = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'Mastercard',
            'disabled_at' => null
        ]);

        // Create a debit card for another user (should not be visible)
        DebitCard::factory()->create([
            'user_id' => $this->otherUser->id,
            'type' => 'Visa'
        ]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonFragment([
                'id' => $debitCard1->id,
                'type' => 'Visa',
                'is_active' => true
            ])
            ->assertJsonFragment([
                'id' => $debitCard2->id,
                'type' => 'Mastercard',
                'is_active' => true
            ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // Create debit cards for other users
        DebitCard::factory()->create([
            'user_id' => $this->otherUser->id,
            'type' => 'Visa'
        ]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200)
            ->assertJsonCount(0);
    }

    public function testCustomerCanCreateADebitCard()
    {
        $data = [
            'type' => 'Visa'
        ];

        $response = $this->postJson('/api/debit-cards', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active'
            ])
            ->assertJsonFragment([
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
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'Visa'
        ]);

        $response = $this->getJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $debitCard->id,
                'type' => 'Visa',
                'number' => $debitCard->number
            ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->otherUser->id,
            'type' => 'Visa'
        ]);

        $response = $this->getJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(403);
    }

    public function testCustomerCanActivateADebitCard()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'Visa',
            'disabled_at' => now() // Initially disabled
        ]);

        $data = [
            'is_active' => true
        ];

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", $data);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'is_active' => true
            ]);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'disabled_at' => null
        ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'Visa',
            'disabled_at' => null // Initially active
        ]);

        $data = [
            'is_active' => false
        ];

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", $data);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'is_active' => false
            ]);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'disabled_at' => now()->toDateString()
        ]);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'Visa'
        ]);

        $data = [
            'is_active' => 'invalid_value' // Should be boolean
        ];

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", $data);

        $response->assertStatus(422);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'Visa'
        ]);

        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('debit_cards', [
            'id' => $debitCard->id
        ]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'Visa'
        ]);

        // Create a transaction for this debit card
        DebitCardTransaction::factory()->create([
            'debit_card_id' => $debitCard->id,
            'amount' => 1000,
            'currency_code' => 'IDR'
        ]);

        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id
        ]);
    }

    // Extra bonus for extra tests :)
    public function testCustomerCannotUpdateOtherCustomerDebitCard()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->otherUser->id,
            'type' => 'Visa'
        ]);

        $data = [
            'is_active' => false
        ];

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", $data);

        $response->assertStatus(403);
    }

    public function testCustomerCannotDeleteOtherCustomerDebitCard()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->otherUser->id,
            'type' => 'Visa'
        ]);

        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(403);
    }
}
