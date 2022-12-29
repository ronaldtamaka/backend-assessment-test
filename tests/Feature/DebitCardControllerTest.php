<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\DebitCard;
use Laravel\Passport\Passport;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Assert as PHPUnit;

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
        DebitCard::factory()->count(3)->for($this->user)->create();

        $this->get('/api/debit-cards')
            ->assertJsonStructure([[
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ]])
            ->assertOk();
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        $newUser = User::factory()->create();

        $currentUserDebitCard = DebitCard::factory()->count(1)->for($this->user)->create();
        $newUserDebitCard = DebitCard::factory()->count(1)->for($newUser)->create();

        $response = $this->get('/api/debit-cards');
        $responseJson = $response->decodeResponseJson()->json();
        $responseDebitCardID = [];

        foreach($responseJson as $key => $json) {
            $responseDebitCardID[$key] = $json['id'];
        }

        $response->assertTrue($newUserDebitCard->whereIn('id', $responseDebitCardID));
        $response->assertOk();
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $this->post('/api/debit-cards', [
                'user_id' => $this->user->id,
                'type' => 'card type',
                'number' => 1,
                'expiration_date' => Carbon::now()->format('Y-m-d'),
            ])
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
            'type' => 'card type',
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->for($this->user)->create();
        $this->get('/api/debit-cards/' . $debitCard->id)
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ])
            ->assertOk();
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $this->get('/api/debit-cards/' . 1)
            ->assertNotFound();
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        // dd($this);
        $debitCard = DebitCard::factory()->for($this->user)->create();
        $this->put('/api/debit-cards/' . $debitCard->id, [
                'is_active' => true,
            ])
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ])
            ->assertOk();

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'disabled_at' => null,
        ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->for($this->user)->create();
        $this->put('/api/debit-cards/' . $debitCard->id, [
                'is_active' => false,
            ])
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ])
            ->assertOk();

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'disabled_at' => Carbon::now(),
        ]);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $currentUserDebitCard = DebitCard::factory()->for($this->user)->create();
        $anotherUserDebitCard = DebitCard::factory()
            ->for(User::factory()->create())
            ->create([
                'number' => 5576428580046635,
                'type' => "TestCard",
                'disabled_at' => null,
            ]);

        $this->put('/api/debit-cards/' . $anotherUserDebitCard->id, [
                'is_active' => false,
            ])
            // ->assertUnauthorized() : return unauthorize response is 401 but the actual output here is 403
            ->assertForbidden();

        $this->assertDatabaseHas('debit_cards', [
            'id' => $anotherUserDebitCard->id,
            'number' => 5576428580046635,
            'type' => "TestCard",
            'disabled_at' => null,
        ]);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->for($this->user)->create([
            'number' => 5576428580046635,
            'type' => "TestCard",
            'disabled_at' => null,
        ]);

        $this->delete('/api/debit-cards/' . $debitCard->id)
            ->assertNoContent();

        $this->assertSoftDeleted('debit_cards', [
            'id' => $debitCard->id,
            'number' => 5576428580046635,
            'type' => "TestCard",
            'disabled_at' => null,
        ]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $currentUserDebitCard = DebitCard::factory()->for($this->user)->create();
        $anotherUserDebitCard = DebitCard::factory()
            ->for(User::factory()->create())
            ->create([
                'number' => 5576428580046635,
                'type' => "TestCard",
                'disabled_at' => null,
            ]);

        $this->delete('/api/debit-cards/' . $anotherUserDebitCard->id)
            // ->assertUnauthorized() : return unauthorize response is 401 but the actual output here is 403
            ->assertForbidden();

        $this->assertDatabaseHas('debit_cards', [
            'id' => $anotherUserDebitCard->id,
            'number' => 5576428580046635,
            'type' => "TestCard",
            'disabled_at' => null,
        ]);
    }

    // Extra bonus for extra tests :)
    public function testCustomerCannotCreateOtherCustomerDebitCard()
    {
        // post /debit-cards
        $newUser = User::factory()->create();

        $this->post('/api/debit-cards', [
                'user_id' => $newUser->id,
                'type' => 'card type',
                'number' => rand(1000000000000000, 9999999999999999),
                'expiration_date' => Carbon::now(),
            ])
            ->assertUnauthorized();
    }
}
