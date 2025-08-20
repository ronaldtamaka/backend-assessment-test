<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Log;
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
        DebitCard::factory()->count(2)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $otherUser = User::factory()->create();
        DebitCard::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200)
                 ->assertJsonCount(0, 'data');
    }

    public function testCustomerCanCreateADebitCard()
    {
        $payload = ['card_number' => '1234567890123456', 'status' => 'active', 'type' => 'Visa'];

        $response = $this->postJson('/api/debit-cards', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('debit_cards', ['card_number' => '1234567890123456']);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $card = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/debit-cards/{$card->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $card->id);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $otherCard = DebitCard::factory()->create();

        $response = $this->getJson("/api/debit-cards/{$otherCard->id}");

        $response->assertStatus(403);
    }

    public function testCustomerCanActivateADebitCard()
    {
        $card = DebitCard::factory()->create(['user_id' => $this->user->id, 'status' => 'inactive']);

        $response = $this->putJson("/api/debit-cards/{$card->id}", ['status' => 'active']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('debit_cards', ['id' => $card->id, 'status' => 'active']);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $card = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->putJson("/api/debit-cards/{$card->id}", ['card_number' => '123']);

        $response->assertStatus(422);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        $card = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/debit-cards/{$card->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('debit_cards', ['id' => $card->id]);
    }

   public function testCustomerCannotDeleteADebitCardWithTransaction()
    {   
        
   $card = DebitCard::factory()->create([
        'user_id' => $this->user->id
    ]);

    DebitCardTransaction::factory()->create([
        'debit_card_id' => $card->id,
    ]);

   
    $card->refresh();

    Log::info('DEBUG_TEST', [
        'trx_count' => $card->debitCardTransactions()->count()
    ]);

    $this->assertDatabaseHas('debit_card_transactions', [
        'debit_card_id' => $card->id
    ]);

    $response = $this->deleteJson("/api/debit-cards/{$card->id}");

    $response->assertStatus(422)
             ->assertJson([
                 'message' => 'Cannot delete a card with existing transactions.'
             ]);
    }
}