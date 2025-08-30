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
        // get /debit-cards
        DebitCard::factory()->count(2)->active()->create([
            'user_id' => $this->user->id
        ]);
        DebitCard::factory()->count(3)->create();

        $this->getJson('/api/debit-cards')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'number',
                    'type',
                    'expiration_date',
                    'is_active'
                ]
            ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        $debitCards = DebitCard::factory()->count(2)->active()->create([
            'user_id' => $this->user->id
        ]);
        $otherUser = User::factory()->create();
        DebitCard::factory()->count(3)->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->getJson('/api/debit-cards')
            ->assertOk()
            ->assertJsonCount(2);

        $response->assertJsonFragment(['id' => $debitCards[0]->id]);
        $response->assertJsonFragment(['id' => $debitCards[1]->id]);
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $payload = [
            'type' => 'Visa'
        ];
        $this->postJson('/api/debit-cards', $payload)
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active'
            ])
            ->assertJsonPath('type', $payload['type']);

        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
            'type' => $payload['type']
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $this->getJson('/api/debit-cards/' . $debitCard->id)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active'
            ])
            ->assertJsonPath('id', $debitCard->id);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $otherUser = User::factory()->create();
        $debitCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);

        $this->getJson('/api/debit-cards/' . $debitCard->id)
            ->assertForbidden();
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->expired()->create(['user_id' => $this->user->id]);

        $this->assertNotNull($debitCard->disabled_at);

        $payload = ['is_active' => true];

        $this->putJson('/api/debit-cards/' . $debitCard->id, $payload)
            ->assertOk();

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'disabled_at' => null
        ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->active()->create(['user_id' => $this->user->id]);

        $this->assertNull($debitCard->disabled_at);

        $payload = ['is_active' => false];

        $this->putJson('/api/debit-cards/' . $debitCard->id, $payload)
            ->assertOk();

        $this->assertDatabaseMissing('debit_cards', [
            'id' => $debitCard->id,
            'disabled_at' => null
        ]);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $payload = ['is_active' => 'not-a-boolean'];

        $this->putJson('/api/debit-cards/' . $debitCard->id, $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('is_active');
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $this->deleteJson('/api/debit-cards/' . $debitCard->id)
            ->assertNoContent();

        $this->assertSoftDeleted('debit_cards', ['id' => $debitCard->id]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);
        DebitCardTransaction::factory()->create(['debit_card_id' => $debitCard->id]);

        $this->deleteJson('/api/debit-cards/' . $debitCard->id)
            ->assertForbidden();

        $this->assertDatabaseHas('debit_cards', ['id' => $debitCard->id, 'deleted_at' => null]);
    }

    // Extra bonus for extra tests :)
}
