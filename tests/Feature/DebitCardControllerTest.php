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
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->get('/api/debit-cards');

        $response->assertOk();

        $response->assertJsonStructure([
            '*' => [
                'id',
                'number',
                'type',
                'expiration_date',
            ],
        ]);

        $response->assertJsonFragment([
            'id' => $debitCard->id,
            'number' => $debitCard->number,
            'type' => $debitCard->type,
            'expiration_date' => $debitCard->expiration_date->format('Y-m-d H:i:s'),
        ]);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'number' => $debitCard->number,
            'type' => $debitCard->type,
            'expiration_date' => $debitCard->expiration_date->format('Y-m-d H:i:s'),
        ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $debitCard = DebitCard::factory()->active()->create();

        $response = $this->get('/api/debit-cards');

        $response->assertOk();

        $response->assertJsonMissing([
            'id' => $debitCard->id,
            'number' => $debitCard->number,
            'type' => $debitCard->type,
        ]);
    }

    public function testCustomerCanCreateADebitCard()
    {
        $response = $this->post('/api/debit-cards', [
            'type' => 'VISATEST',
        ]);

        $response->assertCreated();

        $response->assertJsonStructure([
            'id',
            'number',
            'type',
            'expiration_date',
        ]);

        $json = $response->json();

        $response->assertJsonFragment([
            'id' => $json['id'],
            'number' => $json['number'],
            'type' => $json['type'],
        ]);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $json['id'],
            'number' => $json['number'],
            'type' => $json['type'],
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->get('/api/debit-cards/' . $debitCard->id);

        $response->assertOk();

        $response->assertJsonStructure([
            'id',
            'number',
            'type',
            'expiration_date',
        ]);

        $response->assertJsonFragment([
            'id' => $debitCard->id,
            'number' => $debitCard->number,
            'type' => $debitCard->type,
            'expiration_date' => $debitCard->expiration_date->format('Y-m-d H:i:s'),
        ]);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'number' => $debitCard->number,
            'type' => $debitCard->type,
            'expiration_date' => $debitCard->expiration_date->format('Y-m-d H:i:s'),
        ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $debitCard = DebitCard::factory()->active()->create();

        $response = $this->get('/api/debit-cards/' . $debitCard->id);

        $response->assertForbidden();
    }

    public function testCustomerCanActivateADebitCard()
    {
        $debitCard = DebitCard::factory()->expired()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->put('/api/debit-cards/' . $debitCard->id, [
            'is_active' => true,
        ]);

        $response->assertOk();

        $response->assertJsonFragment([
            'id' => $debitCard->id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'disabled_at' => null,
        ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->put('/api/debit-cards/' . $debitCard->id, [
            'is_active' => false,
        ]);

        $response->assertOk();

        $response->assertJsonFragment([
            'id' => $debitCard->id,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'disabled_at' => now(),
        ]);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->put('/api/debit-cards/' . $debitCard->id, [
            'is_active' => 'hai',
        ]);

        $response->assertSessionHasErrors('is_active');

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'disabled_at' => null,
        ]);
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
