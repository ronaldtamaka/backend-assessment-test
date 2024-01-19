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
        $debitCard1 = DebitCard::factory()->create(['user_id' => $this->user->id]);
        $debitCard2 = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->get('/debit-cards');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['card_number' => $debitCard1->card_number])
            ->assertJsonFragment(['card_number' => $debitCard2->card_number]);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        $otherUserDebitCard = DebitCard::factory()->create();

        $response = $this->get('/debit-cards');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $debitCardData = ['card_number' => '1234'];

        $response = $this->post('/debit-cards', $debitCardData);

        $response->assertStatus(201);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->get('/debit-cards/{$debitCard->id}');

        $response->assertStatus(200)
            ->assertJson(['card_number' => $debitCard->card_number]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $otherUserDebitCard = DebitCard::factory()->create();

        $response = $this->get('/debit-cards/{$otherUserDebitCard->id}');

        $response->assertStatus(404);
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id, 'is_active' => false]);

        $response = $this->put('/debit-cards/{$debitCard->id}', ['is_active' => true]);

        $response->assertStatus(200)
            ->assertJson(['is_active' => true]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id, 'is_active' => true]);

        $response = $this->put('/debit-cards/{$debitCard->id}', ['is_active' => false]);

        $response->assertStatus(200)
            ->assertJson(['is_active' => false]);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user-id' => $this->user->id]);

        $response = $this->put('/debit-cards/{$debitCard->id}', ['invalid_field' => 'invalid_value']);

        $response->assertStatus(422);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->delete('/debit-cards/{$debitCard->id}');

        $response->assertStatus(204);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);
        $debitCard->transactions()->create(['amount' => 100]);

        $response = $this->delete('/debit-card/{$debitCard->id}');

        $response->assertStatus(422);
    }

    // Extra bonus for extra tests :)
}
