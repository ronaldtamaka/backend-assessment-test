<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Assert as PHPUnit;
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

    /**
     * @throws \Throwable
     */
    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $anotherUser = User::factory()->create();

        $currentUserDebitCard = DebitCard::factory()->count(1)->for($this->user)->create();
        $anotherUserDebitCard = DebitCard::factory()->count(1)->for($anotherUser)->create();

        $response = $this->get('/api/debit-cards');
        $responseJson = $response->decodeResponseJson()->json();
        $responseDebitCardID = [];

        foreach($responseJson as $key => $json) {
            $responseDebitCardID[$key] = $json['id'];
        }

        $response->assertTrue($anotherUserDebitCard->whereIn('id', $responseDebitCardID));
        $response->assertOk();
    }

    public function testCustomerCanCreateADebitCard()
    {
        $this->post('/api/debit-cards', [
            'user_id' => $this->user->id,
            'type' => 'card type',
            'number' => 1,
            'expiration_date' => Carbon::now()->format('Y-m-d'),
        ])->assertJsonStructure([
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
        $this->get('/api/debit-cards/' . 1)
            ->assertNotFound();
    }

    public function testCustomerCanActivateADebitCard()
    {
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
        $debitCard = DebitCard::factory()->for($this->user)->create();
        $this->put('/api/debit-cards/' . $debitCard->id, [
            'is_active' => false,
        ])->assertJsonStructure([
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
        $randomNumber = mt_rand(100000000000000,999999999999999);
        $currentUserDebitCard = DebitCard::factory()->for($this->user)->create();
        $anotherUserDebitCard = DebitCard::factory()
            ->for(User::factory()->create())
            ->create([
                'number' => $randomNumber,
                'type' => "AnyCardTest",
                'disabled_at' => null,
            ]);

        $this->put('/api/debit-cards/' . $anotherUserDebitCard->id, [
            'is_active' => false,
        ])
            // ->assertUnauthorized() : return unauthorize response is 401 but the actual output here is 403
            ->assertForbidden();

        $this->assertDatabaseHas('debit_cards', [
            'id' => $anotherUserDebitCard->id,
            'number' => $randomNumber,
            'type' => "AnyCardTest",
            'disabled_at' => null,
        ]);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        $randomNumber = mt_rand(100000000000000,999999999999999);
        $debitCard = DebitCard::factory()->for($this->user)->create([
            'number' => $randomNumber,
            'type' => "AnyCardTest",
            'disabled_at' => null,
        ]);

        $this->delete('/api/debit-cards/' . $debitCard->id)
            ->assertNoContent();

        $this->assertSoftDeleted('debit_cards', [
            'id' => $debitCard->id,
            'number' => $randomNumber,
            'type' => "AnyCardTest",
            'disabled_at' => null,
        ]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        $randomNumber = mt_rand(100000000000000,999999999999999);
        $currentUserDebitCard = DebitCard::factory()->for($this->user)->create();
        $anotherUserDebitCard = DebitCard::factory()
            ->for(User::factory()->create())
            ->create([
                'number' => $randomNumber,
                'type' => "AnyCardTest",
                'disabled_at' => null,
            ]);

        $this->delete('/api/debit-cards/' . $anotherUserDebitCard->id)
            ->assertForbidden();
            // ->assertUnauthorized() : return unauthorize response is 401 but the actual output here is 403

        $this->assertDatabaseHas('debit_cards', [
            'id' => $anotherUserDebitCard->id,
            'number' => $randomNumber,
            'type' => "AnyCardTest",
            'disabled_at' => null,
        ]);
    }

    public function testCustomerCannotCreateOtherCustomerDebitCard()
    {
        // post /debit-cards
        $newUser = User::factory()->create();

        $this->post('/api/debit-cards', [
            'user_id' => $newUser->id,
            'type' => 'card_type',
            'number' => rand(100000000000000, 999999999999999),
            'expiration_date' => Carbon::now(),
        ])->assertUnauthorized();
    }
}
