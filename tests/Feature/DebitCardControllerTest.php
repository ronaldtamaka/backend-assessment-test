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
        DebitCard::factory()->for($this->user)->count(3)->create(['disabled_at' => null]);
        DebitCard::factory()->for($this->user)->create(['disabled_at' => now()]); // Inactive card for the user
        DebitCard::factory()->create(['disabled_at' => null]); // Active card for another user

        $response = $this->getJson('/api/debit-cards');

        $response->assertOk()
            ->assertJsonCount(3) // Assert count on the top-level array
            ->assertJsonStructure([
                '*' => [ // Assert structure on each item in the array
                    'id',
                    'number',
                    'type',
                    'expiration_date',
                    'is_active',
                ]
            ])
            ->assertJsonFragment(['is_active' => true])
            ->assertJsonMissing(['is_active' => false]); // Ensure only active cards belonging to the user are returned
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
