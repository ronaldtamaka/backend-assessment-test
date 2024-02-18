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
        DebitCard::factory()
            ->count(3)
            ->active()
            ->for($this->user)
            ->create();

        $response = $this->getJson('api/debit-cards');
        $response
            ->assertOk()
            ->assertJsonCount(3)
            ->assertJsonStructure(['*' => ['id', 'number', 'type', 'expiration_date', 'is_active']]);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $response = $this->getJson('api/debit-cards');
        $response
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function testCustomerCanCreateADebitCard()
    {
        $response = $this->postJson('api/debit-cards', ['type' => 'foo']);
        $response
            ->assertCreated()
            ->assertJson(['type' => 'foo'])
            ->assertJsonStructure(['id', 'number', 'type', 'expiration_date', 'is_active']);

        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
            'type' => 'foo',
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $debitCard = DebitCard::factory()
            ->active()
            ->for($this->user)
            ->create();

        $response = $this->getJson("api/debit-cards/{$debitCard->id}");
        $response
            ->assertOk()
            ->assertJson(['number' => $debitCard->number, 'type' => $debitCard->type])
            ->assertJsonStructure(['id', 'number', 'type', 'expiration_date', 'is_active']);
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
