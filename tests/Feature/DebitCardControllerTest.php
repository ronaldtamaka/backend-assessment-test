<?php

namespace Tests\Feature;

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
        $debitCards = DebitCard::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'number',
                        'type',
                        'expiration_date',
                        'is_active'
                    ]
                ]
            ]);

        foreach ($response->json('data') as $card) {
            $this->assertEquals($this->user->id, DebitCard::find($card['id'])->user_id);
        }
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $otherUser = User::factory()->create();
        
        $ownDebitCards = DebitCard::factory()->count(2)->create([
            'user_id' => $this->user->id
        ]);

        $otherDebitCards = DebitCard::factory()->count(3)->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $returnedCardIds = collect($response->json('data'))->pluck('id');
        foreach ($ownDebitCards as $card) {
            $this->assertTrue($returnedCardIds->contains($card->id));
        }
        foreach ($otherDebitCards as $card) {
            $this->assertFalse($returnedCardIds->contains($card->id));
        }
    }

    public function testCustomerCanCreateADebitCard()
    {
        $debitCardData = [
            'type' => 'visa',
            'expiration_date' => now()->addYears(3)->format('Y-m-d'),
        ];

        $response = $this->postJson('/api/debit-cards', $debitCardData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'number',
                    'type',
                    'expiration_date',
                    'is_active'
                ]
            ]);

        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
            'type' => 'visa',
            'is_active' => true
        ]);

        $createdCard = DebitCard::where('user_id', $this->user->id)->first();
        $this->assertNotNull($createdCard->number);
        $this->assertEquals(16, strlen($createdCard->number));
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'number',
                    'type',
                    'expiration_date',
                    'is_active'
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => $debitCard->id,
                    'number' => $debitCard->number,
                    'type' => $debitCard->type,
                    'expiration_date' => $debitCard->expiration_date,
                    'is_active' => $debitCard->is_active,
                ]
            ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->getJson("/api/debit-cards/{$otherDebitCard->id}");

        $response->assertStatus(404);
    }

    public function testCustomerCanActivateADebitCard()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => false
        ]);

        $updateData = [
            'is_active' => true
        ];

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $debitCard->id,
                    'number' => $debitCard->number,
                    'type' => $debitCard->type,
                    'expiration_date' => $debitCard->expiration_date,
                    'is_active' => true
                ]
            ]);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'is_active' => true
        ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true
        ]);

        $updateData = [
            'is_active' => false
        ];

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $debitCard->id,
                    'number' => $debitCard->number,
                    'type' => $debitCard->type,
                    'expiration_date' => $debitCard->expiration_date,
                    'is_active' => false
                ]
            ]);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'is_active' => false
        ]);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);

        $invalidData = [
            'expiration_date' => now()->subDay()->format('Y-m-d')
        ];

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['expiration_date']);

        $invalidTypeData = [
            'type' => 'invalid_type'
        ];

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", $invalidTypeData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);

        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->putJson("/api/debit-cards/{$otherDebitCard->id}", ['is_active' => false]);

        $response->assertStatus(404);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('debit_cards', [
            'id' => $debitCard->id
        ]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);

        // Create a transaction for the debit card
        DebitCardTransaction::factory()->create([
            'debit_card_id' => $debitCard->id
        ]);

        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['debit_card']);

        // Verify card is not deleted
        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'deleted_at' => null
        ]);
    }

    // Extra bonus for extra tests :)
}
