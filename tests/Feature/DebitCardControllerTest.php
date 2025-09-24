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
        DebitCard::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'disabled_at' => null,
        ]);
    
        $response = $this->actingAs($this->user)->getJson('/api/debit-cards');

        $response->assertStatus(200);

        $json = $response->json();

        $this->assertCount(2, $json);

        $response->assertJsonStructure([
            '*' => [
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active'
            ]
        ]);
        
        foreach ($json as $card) {
                $this->assertEquals($this->user->id, DebitCard::find($card['id'])->user_id);
        }
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $otherUser = User::factory()->create();
        
        $ownDebitCards = DebitCard::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'disabled_at' => null,

        ]);

        $otherDebitCards = DebitCard::factory()->count(3)->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200);
        
        $json = $response->json();

        $this->assertCount(2, $json);

        $returnedCardIds = collect($json)->pluck('id');

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
                    'id',
                    'number',
                    'type',
                    'expiration_date',
                    'is_active'
            ]);

        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
            'type' => 'visa'            
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
                    'id',
                    'number',
                    'type',
                    'expiration_date',
                    'is_active'
            ])
            ->assertJson([
                    'id' => $debitCard->id
            ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->getJson("/api/debit-cards/{$otherDebitCard->id}");

        $response->assertStatus(403);
    }

    public function testCustomerCanActivateADebitCard()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'disabled_at' => now(),

        ]);

        $updateData = [
            'is_active' => true
        ];

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                    'id' => $debitCard->id,
                    'number' => $debitCard->number,
                    'type' => $debitCard->type,
                    'expiration_date' => $debitCard->expiration_date->format('Y-m-d H:i:s'), // full datetime
                    'is_active' => true
            ]);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'disabled_at' => null,
        ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'disabled_at' => null,
        ]);

        $updateData = ['is_active' => false];

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", $updateData);


        $response->assertStatus(200)
            ->assertJson([
                    'id' => $debitCard->id,
                    'number' => $debitCard->number,
                    'type' => $debitCard->type,
                    'expiration_date' => $debitCard->expiration_date->format('Y-m-d H:i:s'), // full datetime
                    'is_active' => false,
            ]);

        // pastikan disabled_at diisi (tidak null)
        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
        ]);

        $this->assertNotNull(DebitCard::find($debitCard->id)->disabled_at);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $invalidData = [
            'is_active' => 'aktif'
        ];

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", $invalidData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['is_active']);

        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->putJson("/api/debit-cards/{$otherDebitCard->id}", [
            'is_active' => false
        ]);

        $response->assertStatus(403);
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

        DebitCardTransaction::factory()->create([
            'debit_card_id' => $debitCard->id
        ]);

        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'deleted_at' => null
        ]);
    }

    // Extra bonus for extra tests :)
}
