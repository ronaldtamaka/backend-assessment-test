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
        // get /debit-cards
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
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
