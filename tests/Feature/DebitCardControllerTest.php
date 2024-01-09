<?php

namespace Tests\Feature;

use App\Http\Resources\DebitCardResource;
use App\Models\DebitCard;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        // get /debit-cards
        // empty debit card
        $response = $this->getJson('/api/debit-cards');
        // dd($response);
        $response
            ->assertOk()
            ->assertJson([])
            ->assertJsonMissing([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ]);

        $this->assertDatabaseMissing('debit_cards', [
            'user_id' => $this->user->id,
        ]);

        // has debit card
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $this->user->id
        ]);
        $response = $this->getJson('/api/debit-cards');
        // dd($response);
        $response
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'number',
                    'type',
                    'expiration_date',
                    'is_active'
                ],
            ]);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'number' => $debitCard->number,
            'type' => $debitCard->type,
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
