<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson('/api/debit-cards');
        $response
            ->assertOk();
        
        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'number' => $debitCard->number,
            'type' => $debitCard->type,
        ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        $newUser = User::factory()->create();
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $newUser->id
        ]);

        $response = $this->getJson('/api/debit-cards');
        $response
            ->assertOk();
    
        $response->assertJsonMissing([
            'id' => $debitCard->id,
            'number' => $debitCard->number,
            'type' => $debitCard->type,
        ]);
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $response = $this->postJson('/api/debit-cards', [
            'type' => 'TEST_TYPE',
        ]);
        $response->assertCreated();
        $json = $response->json();

        $this->assertDatabaseHas('debit_cards', [
            'id' => $json['id'],
            'number' => $json['number'],
            'type' => $json['type'],
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson("/api/debit-cards/{$debitCard->id}");
        $response
            ->assertOk();

        $this->assertDatabaseHas('debit_cards', [
            'id' => $response->json('id'),
            'user_id' => $this->user->id,
        ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $newUser = User::factory()->create();
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $newUser->id
        ]);

        $response = $this->getJson("/api/debit-cards/{$debitCard->id}");
        $response
            ->assertForbidden();
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", [
            'is_active' => true
        ]);
        $response
            ->assertOk();

        $json = $response->json();
        $this->assertTrue($json['is_active']);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", [
            'is_active' => false
        ]);
        $response
            ->assertOk();

        $json = $response->json();
        $this->assertFalse($json['is_active']);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->active()->create();

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", [
            'is_active' => 'string'
        ]);
        $response
            ->assertForbidden();
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");

        $model = $this->user->debitCards()->onlyTrashed()->where('id', $debitCard->id)->first();
        $this->assertFalse(is_null($model->deleted_at));
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->active()->create();

        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");
        $response
            ->assertForbidden();
    }

    // Extra bonus for extra tests :)
}
