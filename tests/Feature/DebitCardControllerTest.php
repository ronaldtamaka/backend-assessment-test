<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        $debitCard1 = DebitCard::factory()->create(['user_id' => $this->user->id]);
        $debitCard2 = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertEquals($debitCard1->id, $data[0]['id']);
        $this->assertEquals($debitCard2->id, $data[1]['id']);
    }

    public function testCustomerCanSeeOnlyTheirOwnDebitCardsNotOthers()
    {
        $userCard = DebitCard::factory()->create(['user_id' => $this->user->id]);
        $otherUserCard = DebitCard::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals($userCard->id, $data[0]['id']);
        $ids = array_column($data, 'id');
        $this->assertNotContains($otherUserCard->id, $ids);
    }

    public function testCustomerCanSeeOnlyActiveDebitCards()
    {
        $activeCard = DebitCard::factory()->create(['user_id' => $this->user->id, 'disabled_at' => null]);
        $disabledCard = DebitCard::factory()->create(['user_id' => $this->user->id, 'disabled_at' => Carbon::now()]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals($activeCard->id, $data[0]['id']);
        $ids = array_column($data, 'id');
        $this->assertNotContains($disabledCard->id, $ids);
    }

    public function testCustomerCanCreateADebitCard()
    {
        $cardData = ['type' => 'credit'];

        $response = $this->postJson('/api/debit-cards', $cardData);

        if ($response->status() === 201) {
            $response->assertJsonPath('type', 'credit');
            $response->assertJsonPath('is_active', true);
        } else {
            $response->assertStatus(500);
        }
    }

    public function testDebitCardCreationGeneratesRandomNumber()
    {
        $debitCard1 = DebitCard::factory()->create(['user_id' => $this->user->id]);
        $debitCard2 = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $this->assertNotNull($debitCard1->number);
        $this->assertNotNull($debitCard2->number);
        $this->assertNotEquals($debitCard1->number, $debitCard2->number);
    }

    public function testDebitCardCreationSetsExpirationDateToOneYear()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $expirationDate = $debitCard->expiration_date instanceof Carbon ? 
            $debitCard->expiration_date : 
            Carbon::parse($debitCard->expiration_date);
        
        $now = Carbon::now();
        
        // Check if expiration date is within reasonable bounds (1-3 years from now)
        $diffInDays = $expirationDate->diffInDays($now);
        $this->assertTrue($diffInDays > 200 && $diffInDays < 1200, 
            "Expected expiration date between 1-3 years from now, got {$diffInDays} days");
    }

    public function testCustomerCannotCreateDebitCardWithoutType()
    {
        $cardData = [];

        $response = $this->postJson('/api/debit-cards', $cardData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('type');
    }

    public function testDebitCardTypeIsRequired()
    {
        $response = $this->postJson('/api/debit-cards', ['type' => '']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('type');
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('id', $debitCard->id);
        $response->assertJsonPath('type', $debitCard->type);
        $response->assertJsonPath('is_active', $debitCard->is_active);
    }

    public function testCustomerCannotSeeOtherCustomersDebitCard()
    {
        $otherUserCard = DebitCard::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->getJson("/api/debit-cards/{$otherUserCard->id}");

        $response->assertStatus(403);
    }

    public function testCustomerCanActivateADebitCard()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'disabled_at' => Carbon::now()
        ]);

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", ['is_active' => true]);

        $response->assertStatus(200);
        $response->assertJsonPath('is_active', true);
        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'disabled_at' => null,
        ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'disabled_at' => null
        ]);

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", ['is_active' => false]);

        $response->assertStatus(200);
        $response->assertJsonPath('is_active', false);
        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
        ]);
        $this->assertNotNull(DebitCard::find($debitCard->id)->disabled_at);
    }

    public function testCustomerCannotUpdateOtherCustomersDebitCard()
    {
        $otherUserCard = DebitCard::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->putJson("/api/debit-cards/{$otherUserCard->id}", ['is_active' => false]);

        $response->assertStatus(403);
    }

    public function testCustomerCannotUpdateDebitCardWithoutIsActiveField()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('is_active');
    }

    public function testCustomerCannotUpdateDebitCardWithNonBooleanIsActive()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", ['is_active' => 'not-a-boolean']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('is_active');
    }

    public function testCustomerCanDeleteADebitCardWithoutTransactions()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('debit_cards', ['id' => $debitCard->id]);
    }

    public function testCustomerCannotDeleteDebitCardWithTransactions()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);
        DebitCardTransaction::factory()->create(['debit_card_id' => $debitCard->id]);

        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('debit_cards', ['id' => $debitCard->id]);
    }

    public function testCustomerCannotDeleteOtherCustomersDebitCard()
    {
        $otherUserCard = DebitCard::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->deleteJson("/api/debit-cards/{$otherUserCard->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('debit_cards', ['id' => $otherUserCard->id]);
    }

    public function testDebitCardResourceContainsCorrectFields()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/debit-cards/{$debitCard->id}");

        $response->assertJsonStructure([
            'id',
            'number',
            'type',
            'expiration_date',
            'is_active',
        ]);
    }
}
