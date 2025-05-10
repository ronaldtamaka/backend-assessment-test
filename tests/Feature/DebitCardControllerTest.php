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
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        DebitCard::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json());
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $user2 = User::factory()->create();
        DebitCard::factory()->count(2)->create(['user_id' => $user2->id]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json());
    }

    public function testCustomerCanCreateADebitCard()
    {
        $data = ['type' => 'gpn'];

        $response = $this->postJson('/api/debit-cards', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
            'type' => 'gpn'
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $debitCard->id,
                'type' => $debitCard->type,
                'number' => $debitCard->number,
                'is_active' => $debitCard->is_active,
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
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => false
        ]);

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", [
            'is_active' => true
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'is_active' => true,
            'disabled_at' => null,
        ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true
        ]);

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", [
            'is_active' => false
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('debit_cards', [
            'id' => $debitCard->id,
            'is_active' => true,
        ]);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", [
            'is_active' => 'invalid'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['is_active']);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('debit_cards', ['id' => $debitCard->id]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);
        DebitCardTransaction::factory()->create(['debit_card_id' => $debitCard->id]);

        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('debit_cards', ['id' => $debitCard->id]);
    }

    // Extra bonus for extra tests :)

    public function testOnlyOneDebitCardCanBeActive()
    {
        $card1 = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $card2 = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => false,
        ]);

        $response = $this->putJson("api/debit-cards/{$card2->id}", ['is_active' => true]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $card2->id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $card1->id,
            'is_active' => false,
        ]);
    }

    public function testCannotCreateDebitCardWithDuplicateNumber()
    {
        $number = '1234567890123456';

        \App\Models\DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'number' => $number,
        ]);

        $response = $this->postJson('api/debit-cards', [
            'type' => 'gpn',
            'number' => $number,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['number']);
    }


    public function testCannotUseInactiveCardForTransaction()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => false,
        ]);

        $response = $this->postJson("api/debit-card-transactions", [
            'debit_card_id' => $debitCard->id,
            'amount' => 10000,
            'type' => 'purchase',
        ]);

        $response->assertStatus(403);
    }


    public function testSoftDeletedCardsAreNotReturned()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $debitCard->delete();

        $response = $this->getJson('api/debit-cards');

        $response->assertStatus(200);
        $this->assertFalse(collect($response->json())->contains('id', $debitCard->id));
    }


    public function testUnauthenticatedUserCannotAccessDebitCards()
    {
        Passport::actingAs(null); // logout user

        $response = $this->getJson('api/debit-cards');

        $response->assertStatus(401); // Unauthorized
    }

    public function testCustomerCannotAccessOtherCustomerDebitCardDetails()
    {
        $otherUser = User::factory()->create();

        $card = DebitCard::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->getJson("api/debit-cards/{$card->id}");

        $response->assertStatus(403); // Forbidden
    }

    public function testCreateDebitCardValidationFails()
    {
        $response = $this->postJson('api/debit-cards', []); // Empty payload

        $response->assertStatus(422); // Unprocessable Entity
        $response->assertJsonValidationErrors(['type', 'number']);
    }

    public function testCardNumberMustBeSixteenDigits()
    {
        $response = $this->postJson('api/debit-cards', [
            'type' => 'gpn',
            'number' => '1234',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['number']);
    }

    public function testDeletingCardSoftDeletesIt()
    {
        $card = DebitCard::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("api/debit-cards/{$card->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('debit_cards', ['id' => $card->id]);
    }

    public function testCustomerCannotUpdateOtherUsersCard()
    {
        $otherUser = User::factory()->create();

        $card = DebitCard::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->putJson("api/debit-cards/{$card->id}", [
            'type' => 'visa',
        ]);

        $response->assertStatus(403);
    }
}
