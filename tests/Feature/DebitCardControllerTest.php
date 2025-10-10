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
        DebitCard::factory()->count(3)->active()->create(['user_id' => $this->user->id]);
        DebitCard::factory()->count(3)->create();
        $this->getJson('/api/debit-cards')->assertOk()->assertJsonCount(3)->assertJsonStructure(['*' => ['id', 'number', 'type', 'expiration_date',]]);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $otherUser = User::factory()->create();
        DebitCard::factory()->count(3)->active()->create(['user_id' => $otherUser->id]);
        $this->getJson('/api/debit-cards')->assertOk()->assertJsonCount(0);
    }

    public function testCustomerCanCreateADebitCard()
    {
        $payload = [
            'type' => 'Visa'
        ];

        $this->postJson('/api/debit-cards', $payload)
            ->assertCreated()
            ->assertJsonStructure(['id', 'number', 'type', 'expiration_date',]);

        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
            'type' => $payload['type'],
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $card = DebitCard::factory()->active()->create(['user_id' => $this->user->id]);

        $this->getJson("/api/debit-cards/{$card->id}")
            ->assertOk()
            ->assertJsonStructure(['id', 'number', 'type', 'expiration_date',]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
    }

    // Extra bonus for extra tests :)
}
