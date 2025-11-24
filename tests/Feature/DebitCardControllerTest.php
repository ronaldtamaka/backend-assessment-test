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

        $debitCard1 = DebitCard::factory()->active()->create(['user_id' => $this->user->id]);
        $debitCard2 = DebitCard::factory()->active()->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/debit-cards');
        $response->assertStatus(200);
        $response->assertJsonCount(2);

        $response->assertJson([
            ['id' => $debitCard1->id],
            ['id' => $debitCard2->id],
        ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $otherUser = User::factory()->create();
        DebitCard::factory()->active()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson('/api/debit-cards');
        $response->assertStatus(200);
        $response->assertJsonCount(0);
        $response->assertJson([]);
    }

    public function testCustomerCanCreateADebitCard()
    {
        $response = $this->postJson('/api/debit-cards', [
            'type' => 'Mastercard',
        ]);
        $response->assertStatus(201);
        $response->assertJson([
            'id' => $this->user->debitCards->last()->id,
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $this->user->debitCards()->saveMany(DebitCard::factory()->count(2)->active()->make());
        $response = $this->getJson('/api/debit-cards/' . $this->user->debitCards->last()->id);
        $response->assertStatus(200);
        $response->assertJson([
            'id' => $this->user->debitCards->last()->id,
        ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $otherUser = User::factory()->create();
        $otherUserDebitCard = DebitCard::factory()->active()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson('/api/debit-cards/' . $otherUserDebitCard->id);
        $response->assertStatus(403);
    }

    public function testCustomerCanActivateADebitCard()
    {
        $this->user->debitCards()->saveMany(DebitCard::factory()->count(2)->active()->make());
        $response = $this->putJson('/api/debit-cards/' . $this->user->debitCards->last()->id, [
            'is_active' => true,
        ]);
        $response->assertStatus(200);
        $response->assertJson([
            'id' => $this->user->debitCards->last()->id,
        ]);
        $this->assertNull($this->user->debitCards->last()->disabled_at);
        $this->assertTrue($this->user->debitCards->last()->is_active);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        $this->user->debitCards()->saveMany(DebitCard::factory()->count(2)->active()->make());
        $response = $this->putJson('/api/debit-cards/' . $this->user->debitCards->last()->id, [
            'is_active' => false,
        ]);
        $response->assertStatus(200);
        $response->assertJson([
            'id' => $this->user->debitCards->last()->id,
        ]);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $this->user->debitCards()->saveMany(DebitCard::factory()->count(2)->active()->make());
        $response = $this->putJson('/api/debit-cards/' . $this->user->debitCards->last()->id, [
            'is_active' => 'true',
        ]);
        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The given data was invalid.',
            'errors' => [
                'is_active' => ['The is active field must be true or false.'],
            ],
        ]);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        $this->user->debitCards()->saveMany(DebitCard::factory()->count(2)->active()->make());
        $response = $this->deleteJson('/api/debit-cards/' . $this->user->debitCards->last()->id);
        $response->assertStatus(204);
        $this->assertNull($this->user->debitCards->last()->deleted_at);
        $this->assertTrue($this->user->debitCards->last()->is_active);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        $this->user->debitCards()->saveMany(DebitCard::factory()->count(2)->active()->make());
        $this->user->debitCards->last()->debitCardTransactions()->saveMany(DebitCardTransaction::factory()->count(2)->make());
        $response = $this->deleteJson('/api/debit-cards/' . $this->user->debitCards->last()->id);
        $response->assertStatus(403);
        $this->assertNull($this->user->debitCards->last()->deleted_at);
        $this->assertTrue($this->user->debitCards->last()->is_active);
    }
}
