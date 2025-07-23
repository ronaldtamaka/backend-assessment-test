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
        DebitCard::factory()->count(2)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        DebitCard::factory()->create(['user_id' => $this->user->id]);
        $otherUser = User::factory()->create();
        DebitCard::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $cardData = [
            'type' => 'visa',
            'number' => '1234567812345678',
            'expiration_date' => \Carbon\Carbon::now()->addYear()->format('Y-m-d'),
        ];

        $response = $this->postJson('/api/debit-cards', $cardData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
            'number' => $cardData['number'],
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/debit-cards/' . $debitCard->id);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $debitCard->id
                ]
            ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $otherUser = User::factory()->create();
        $otherUsersCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson('/api/debit-cards/' . $otherUsersCard->id);

        $response->assertStatus(404);
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => false,
        ]);

        $response = $this->putJson('/api/debit-cards/' . $debitCard->id, ['is_active' => true]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'is_active' => true,
        ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $response = $this->putJson('/api/debit-cards/' . $debitCard->id, ['is_active' => false]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'is_active' => false,
        ]);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);
        $invalidData = ['type' => 'invalid-type']; // Assuming 'type' has specific allowed values

        $response = $this->putJson('/api/debit-cards/' . $debitCard->id, $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('type');
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);
        DebitCardTransaction::factory()->create(['debit_card_id' => $debitCard->id]);
        $response = $this->deleteJson('/api/debit-cards/' . $debitCard->id);

        $response->assertStatus(409);
        $this->assertDatabaseHas('debit_cards', ['id' => $debitCard->id]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);
        Tests\Feature\Passport::flushClient(); 

        $this->getJson('/api/debit-cards')->assertStatus(401);
        $this->postJson('/api/debit-cards')->assertStatus(401);
        $this->getJson('/api/debit-cards/' . $debitCard->id)->assertStatus(401);
        $this->putJson('/api/debit-cards/' . $debitCard->id)->assertStatus(401);
        $this->deleteJson('/api/debit-cards/' . $debitCard->id)->assertStatus(401);
    }

    // Extra bonus for extra tests :)
    public function testCustomerCannotDeleteAnotherUsersDebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $otherUser = User::factory()->create();
        $otherUsersCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->deleteJson('/api/debit-cards/' . $otherUsersCard->id);

        $response->assertStatus(404);
    }

}
