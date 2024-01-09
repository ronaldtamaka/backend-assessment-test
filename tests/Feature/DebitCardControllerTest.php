<?php

namespace Tests\Feature;

use App\Http\Resources\DebitCardResource;
use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
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

    public function testCustomerCanCreateADebitCardWithInvalid()
    {
        $response = $this->postJson('/api/debit-cards', [
            'type' => null
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type'])
            ->assertJson([])
            ->assertJsonMissing([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ]);
        $this->assertDatabaseMissing('debit_cards', [
            'id' => $response->json(),
            'user_id' => $this->user->id,
            'number' => $response->json(),
            'type' => 'TEST',
            'expiration_date' => $response->json(),
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->for($this->user)->create();
        $this->getJson('/api/debit-cards/' . $debitCard->id)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active'
            ]);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'number' => $debitCard->number,
            'type' => $debitCard->type,
        ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $this->getJson('/api/debit-cards/2')->assertNotFound()
            ->assertJson([])
            ->assertJsonMissing([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ]);
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->for($this->user)->create();

        $response = $this->putJson('api/debit-cards/' . $debitCard->id, [
            'is_active' => true
        ]);
        // dd($response->json()['id']);
        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active'
            ]);
        $debitCard->refresh();
        $this->assertTrue($debitCard->is_active);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->for($this->user)->create();

        $response = $this->putJson('api/debit-cards/' . $debitCard->id, [
            'is_active' => false
        ]);
        // dd($response->json()['id']);
        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active'
            ]);

        $debitCard->refresh();
        $this->assertFalse($debitCard->is_active);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->for($this->user)->create();

        $response = $this->putJson('api/debit-cards/' . $debitCard->id, [
            'is_active' => null
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['is_active'])
            ->assertJson([])
            ->assertJsonMissing([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ]);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->for($this->user)->create();
        $this->deleteJson('/api/debit-cards/' . $debitCard->id)->assertNoContent();
        $this->assertSoftDeleted('debit_cards', [
            'id' => $debitCard->id,
        ]);
        $debitCard->refresh();
        $this->assertFalse(is_null($debitCard->deleted_at));
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);
        $transaction = DebitCardTransaction::factory()->create(['debit_card_id' => $debitCard->id]);

        $response = $this->deleteJson('/api/debit-cards/' . $debitCard->id)->assertForbidden();
        $this->assertDatabaseHas('debit_cards', ['id' => $debitCard->id]);
        $this->assertDatabaseHas('debit_card_transactions', ['id' => $transaction->id]);
    }

    // Extra bonus for extra tests :)
}
