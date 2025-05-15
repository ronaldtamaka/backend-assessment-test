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
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
        $card = DebitCard::factory()->for($this->user)->create();

        $this->getJson('/api/debit-cards')
            ->assertOk()
            ->assertJsonCount(1);
        $this->assertDatabaseHas('debit_cards', [
                'id' => $card->id,
                'user_id' => $this->user->id,
                'deleted_at' => null,
            ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
        $otherUser = User::factory()->create();
        $card = DebitCard::factory()->for($otherUser)->create();

        $this->getJson("/api/debit-cards/{$card->id}")
            ->assertForbidden();
    }

    public function testCustomerCanCreateADebitCard()
    {
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
        $payload = [
            'type' => 'Visa',
        ];

        $response = $this->postJson('/api/debit-cards', $payload);

        $responseData = $response->json();
        $cardNumber = $responseData['number'];

        $response->assertCreated()
                ->assertJsonFragment(['number' => $cardNumber]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
        $card = DebitCard::factory()->for($this->user)->create();

        $this->getJson("/api/debit-cards/{$card->id}")
            ->assertOk()
            ->assertJsonFragment(['id' => $card->id]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
        $otherUser = User::factory()->create();
        $card = DebitCard::factory()->for($otherUser)->create();

        $this->getJson("/api/debit-cards/{$card->id}")
            ->assertForbidden();
    }

    public function testCustomerCanActivateADebitCard()
    {
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
        $card = DebitCard::factory()->for($this->user)->create(['is_active' => false]);

        $this->putJson("/api/debit-cards/{$card->id}", ['is_active' => true])
            ->assertOk()
            ->assertJsonFragment(['is_active' => true]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
        $card = DebitCard::factory()->for($this->user)->create(['is_active' => true]);

        $this->putJson("/api/debit-cards/{$card->id}", ['is_active' => false])
            ->assertOk()
            ->assertJsonFragment(['is_active' => false]);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
        $card = DebitCard::factory()->for($this->user)->create();

        $this->putJson("/api/debit-cards/{$card->id}", [
            'number' => '', // invalid
            'expiration_date' => 'not-a-date',
            'is_active' => ''
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['is_active']);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
        $card = DebitCard::factory()->for($this->user)->create();

        $this->deleteJson("/api/debit-cards/{$card->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('debit_cards', ['id' => $card->id]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
        $card = DebitCard::factory()->for($this->user)->create();
        DebitCardTransaction::factory()->for($card)->create();

        $this->deleteJson("/api/debit-cards/{$card->id}")
            ->assertStatus(403);

            $this->assertDatabaseHas('debit_cards', [
                'id' => $card->id,
                'deleted_at' => null, // ensure it's NOT soft-deleted
            ]);
    }
    
}
