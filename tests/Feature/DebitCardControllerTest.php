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

    public function testCustomerCanViewTheirNoDebitCards()
    {
        // get /debit-cards
        // No debit cards associated
        $response = $this->getJson('/api/debit-cards');
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
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        // get /debit-cards
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
        $otherUser = User::factory()->create();
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->getJson('/api/debit-cards');

        $response
            ->assertOk()
            ->assertJson([])
            ->assertJsonMissing([
                'id' => $debitCard->id,
                'number' => $debitCard->number,
                'type' => $debitCard->type,
                'expiration_date' => $debitCard->expiration_date,
                'is_active' => $debitCard->is_active,
            ]);

        $this->assertDatabaseMissing('debit_cards', [
            'id' => $debitCard->id,
            'user_id' => $this->user->id, // Ensure the other user's debit card is not associated with the authenticated user
        ]);
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $response = $this->postJson('api/debit-cards', [
            'type' => 'TEST'
        ]);
        // dd($response->json()['id']);
        $response->assertCreated()
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active'
            ]);

        $expectedExpirationDate = Carbon::parse($response->json()['expiration_date'])->toDateTimeString();

        $this->assertDatabaseHas('debit_cards', [
            'id' => $response->json()['id'],
            'user_id' => $this->user->id,
            'number' => $response->json()['number'],
            'type' => 'TEST',
            'expiration_date' => $expectedExpirationDate,
        ]);
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
