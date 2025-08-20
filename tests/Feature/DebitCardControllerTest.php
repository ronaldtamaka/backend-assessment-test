<?php

namespace Tests\Feature;

use App\Models\DebitCard;
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
        DebitCard::factory()->for($this->user)->count(3)->create(['disabled_at' => null]);
        DebitCard::factory()->for($this->user)->create(['disabled_at' => now()]); // Inactive card for the user
        DebitCard::factory()->create(['disabled_at' => null]); // Active card for another user

        $response = $this->getJson('/api/debit-cards');

        $response->assertOk()
            ->assertJsonCount(3) // Assert count on the top-level array
            ->assertJsonStructure([
                '*' => [ // Assert structure on each item in the array
                    'id',
                    'number',
                    'type',
                    'expiration_date',
                    'is_active',
                ]
            ])
            ->assertJsonFragment(['is_active' => true])
            ->assertJsonMissing(['is_active' => false]); // Ensure only active cards belonging to the user are returned
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        $otherUser = User::factory()->create();
        DebitCard::factory()->for($otherUser)->count(2)->create(['disabled_at' => null]);
        DebitCard::factory()->for($this->user)->count(3)->create(['disabled_at' => null]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertOk()
            ->assertJsonCount(3) // Should only see own cards
            ->assertJsonMissing(['user_id' => $otherUser->id]); // Ensure none of the other user's cards are present
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $payload = ['type' => 'VISA'];
        $response = $this->postJson('/api/debit-cards', $payload);

        $response->assertCreated()
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ]);

        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
            'type' => 'VISA',
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->for($this->user)->create();

        $response = $this->getJson('/api/debit-cards/' . $debitCard->id);

        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ])
            ->assertJsonFragment(['id' => $debitCard->id]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->for($otherUser)->create();

        $response = $this->getJson('/api/debit-cards/' . $otherDebitCard->id);

        $response->assertForbidden(); // Expect 403 Forbidden based on policy
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->for($this->user)->create(['disabled_at' => now()]);

        $response = $this->putJson('/api/debit-cards/' . $debitCard->id, ['is_active' => true]);

        $response->assertOk()
            ->assertJsonFragment(['is_active' => true]);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'disabled_at' => null,
        ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->for($this->user)->create(['disabled_at' => null]);

        $response = $this->putJson('/api/debit-cards/' . $debitCard->id, ['is_active' => false]);

        $response->assertOk()
            ->assertJsonFragment(['is_active' => false]);

        $this->assertNotNull(DebitCard::find($debitCard->id)->disabled_at);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->for($this->user)->create();

        $response = $this->putJson('/api/debit-cards/' . $debitCard->id, ['is_active' => 'not-a-boolean']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['is_active']);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->for($this->user)->create();

        $response = $this->deleteJson('/api/debit-cards/' . $debitCard->id);

        $response->assertNoContent(); // 204 No Content

        $this->assertSoftDeleted('debit_cards', ['id' => $debitCard->id]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
    }

    // Extra bonus for extra tests :)
}
