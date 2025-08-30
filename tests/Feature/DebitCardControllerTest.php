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
        // Create debit cards for the authenticated user
        $debitCards = DebitCard::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'disabled_at' => null
        ]);

        // Create debit cards for another user (should not be visible)
        $otherUser = User::factory()->create();
        DebitCard::factory()->count(2)->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200)
                 ->assertJsonCount(3)
                 ->assertJsonStructure([
                     '*' => [
                         'id',
                         'type',
                         'number',
                         'expiration_date',
                         'is_active'
                     ]
                 ]);

        // Verify only user's cards are returned
        $returnedIds = collect($response->json('data'))->pluck('id')->toArray();
        $expectedIds = $debitCards->pluck('id')->toArray();
        $this->assertEquals(sort($expectedIds), sort($returnedIds));
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // Create debit cards for another user
        $otherUser = User::factory()->create();
        DebitCard::factory()->count(3)->create([
            'user_id' => $otherUser->id
        ]);

        // Create one active debit card for authenticated user
        $myCard = DebitCard::factory()->active()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200)
                 ->assertJsonCount(1);

        // Verify only authenticated user's card is returned
        $this->assertEquals($myCard->id, $response->json('0.id'));
    }

    public function testCustomerCanCreateADebitCard()
    {
        $debitCardData = [
            'type' => 'VISA'
        ];

        $response = $this->postJson('/api/debit-cards', $debitCardData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'id',
                     'type',
                     'number',
                     'expiration_date',
                     'is_active'
                 ]);

        // Verify card was created in database
        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
            'type' => 'VISA',
            'disabled_at' => null
        ]);

        // Verify the created card belongs to authenticated user
        $this->assertEquals('VISA', $response->json('type'));
        $this->assertTrue($response->json('is_active'));
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'MASTERCARD'
        ]);

        $response = $this->getJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'id',
                     'type',
                     'number',
                     'expiration_date',
                     'is_active'
                 ]);

        $this->assertEquals($debitCard->id, $response->json('id'));
        $this->assertEquals($debitCard->type, $response->json('type'));
        $this->assertEquals($debitCard->number, $response->json('number'));
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // Create a debit card for another user
        $otherUser = User::factory()->create();
        $otherUserCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->getJson("/api/debit-cards/{$otherUserCard->id}");

        // Should return 403 Forbidden or 404 Not Found depending on policy implementation
        $this->assertContains($response->getStatusCode(), [403, 404]);
    }

    public function testCustomerCanActivateADebitCard()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'disabled_at' => now() // Create as disabled
        ]);

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", [
            'is_active' => true
        ]);

        $response->assertStatus(200);

        // Verify card is now active
        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'disabled_at' => null
        ]);

        $this->assertTrue($response->json('is_active'));
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'disabled_at' => null // Create as active
        ]);

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", [
            'is_active' => false
        ]);

        $response->assertStatus(200);

        // Verify card is now disabled
        $debitCard->refresh();
        $this->assertNotNull($debitCard->disabled_at);
        $this->assertFalse($response->json('is_active'));
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);

        // Test with invalid data
        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", [
            'is_active' => 'invalid_boolean'
        ]);

        // Should return validation error
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['is_active']);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(204);

        // Verify card was soft deleted
        $this->assertSoftDeleted('debit_cards', [
            'id' => $debitCard->id
        ]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $this->user->id
        ]);

        // Create a transaction for this debit card
        DebitCardTransaction::factory()->create([
            'debit_card_id' => $debitCard->id
        ]);

        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");

        // Should return authorization error (cannot delete card with transactions)
        $this->assertContains($response->getStatusCode(), [403, 422, 409]);

        // Verify card was not deleted
        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'deleted_at' => null
        ]);
    }

    // Extra bonus for extra tests :)
}
