<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\Models\DebitCard;
use App\Models\DebitCardTransaction;

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
        $debitCards = DebitCard::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        $this->actingAs($this->user, 'api')
                    ->getJson('/api/debit-cards')
                    ->assertStatus(200)
                    ->assertJsonStructure([
                    '*' => ['id', 'number', 'type', 'expiration_date', 'is_active']
                ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $otherUser = User::factory()->create();

        $debitCards = DebitCard::factory()->count(3)->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->actingAs($this->user, 'api')
                        ->getJson('/api/debit-cards')
                        ->assertStatus(200);

        foreach ($debitCards as $card) {
            $response->assertJsonMissing([
                'id' => $card->id,
                'number' => $card->number,
                'type' => $card->type,
                'expiration_date' => $card->expiration_date->format('Y-m-d H:i:s'),
                'is_active' => $card->is_active,
            ]);
        }
    }

    public function testCustomerCanCreateADebitCard()
    {
        $payload = [
            'type' => 'Visa'
        ];

        $this->actingAs($this->user, 'api')
                ->postJson('/api/debit-cards', $payload)
                ->assertStatus(201)
                ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active'
            ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);

        $this->actingAs($this->user, 'api')
                ->getJson("/api/debit-cards/{$debitCard->id}")
                ->assertStatus(200)
                ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active'
            ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $otherUser = User::factory()->create();
        $debitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);
        
        $this->actingAs($this->user, 'api')
                ->getJson("/api/debit-cards/{$debitCard->id}")
                ->assertStatus(403)
                ->assertJsonMissing([
                    'id' => $debitCard->id,
                    'number' => $debitCard->number,
                    'type' => $debitCard->type,
                    'expiration_date' => $debitCard->expiration_date->format('Y-m-d H:i:s'),
                    'is_active' => $debitCard->is_active
                ]);
    }

    public function testCustomerCanActivateADebitCard()
    {
        $payload = [
            'is_active' => true
        ];

        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'disabled_at' => now()
        ]);

        $this->actingAs($this->user, 'api')
                ->putJson("/api/debit-cards/{$debitCard->id}", $payload)
                ->assertStatus(200)
                ->assertJsonFragment([
                'id' => $debitCard->id,
                'number' => $debitCard->number,
                'type' => $debitCard->type,
                'expiration_date' => $debitCard->expiration_date->format('Y-m-d H:i:s'),
                'is_active' => true
            ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $payload = [
            'is_active' => false
        ];

        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);

        $this->actingAs($this->user, 'api')
                ->putJson("/api/debit-cards/{$debitCard->id}", $payload)
                ->assertStatus(200)
                ->assertJsonFragment([
                'id' => $debitCard->id,
                'number' => $debitCard->number,
                'type' => $debitCard->type,
                'expiration_date' => $debitCard->expiration_date->format('Y-m-d H:i:s'),
                'is_active' => false
            ]);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $payload = [
            'is_active' => 'not-a-boolean'
        ];
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);

        $this->actingAs($this->user, 'api')
                ->putJson("/api/debit-cards/{$debitCard->id}", $payload)
                ->assertStatus(422)
                ->assertJsonValidationErrors(['is_active']);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);

        $this->actingAs($this->user, 'api')
                ->deleteJson("/api/debit-cards/{$debitCard->id}")
                ->assertStatus(204);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);

        DebitCardTransaction::factory()->create([
            'amount' => 10000,
            'currency_code' => 'IDR',
            'debit_card_id' => $debitCard->id
        ]);

        $this->actingAs($this->user, 'api')
                ->deleteJson("/api/debit-cards/{$debitCard->id}")
                ->assertForbidden();
    }

    // Extra bonus for extra tests :)
    public function testCustomerCannotDeleteADebitCardOfOtherCustomers()
    {
        $otherUser = User::factory()->create();
        $debitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $this->actingAs($this->user, 'api')
                ->deleteJson("/api/debit-cards/{$debitCard->id}")
                ->assertStatus(403)
                ->assertJsonMissing([
                    'id' => $debitCard->id,
                    'number' => $debitCard->number,
                    'type' => $debitCard->type,
                    'expiration_date' => $debitCard->expiration_date->format('Y-m-d H:i:s'),
                    'is_active' => $debitCard->is_active
                ]);
    }

    public function testCustomerCannotUpdateADebitCardOfOtherCustomers()
    {
        $otherUser = User::factory()->create();
        $debitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $payload = [
            'is_active' => false
        ];

        $this->actingAs($this->user, 'api')
                ->putJson("/api/debit-cards/{$debitCard->id}", $payload)
                ->assertStatus(403)
                ->assertJsonMissing([
                    'id' => $debitCard->id,
                    'number' => $debitCard->number,
                    'type' => $debitCard->type,
                    'expiration_date' => $debitCard->expiration_date->format('Y-m-d H:i:s'),
                    'is_active' => $debitCard->is_active
                ]);
    }
}
