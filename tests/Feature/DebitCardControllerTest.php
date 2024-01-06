<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DebitCard;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
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
        $response = $this->get('/api/debit-cards');
        $response->assertStatus(200);
        $response->assertOk();
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $newUser = User::factory()->create();
        DebitCard::factory()->count(1)->for($newUser)->create();
        DebitCard::factory()->count(1)->for($this->user)->create();
        $response = $this->get('/api/debit-cards');
        $response->assertStatus(200);
        $response->assertOk();
    }

    public function testCustomerCanCreateADebitCard()
    {
        $data = [
            'user_id' => $this->user->id,
            'type' => 'card type',
            'number' => 1,
            'expiration_date' => Carbon::now()->format('Y-m-d')
        ];
        $response = $this->post('/api/debit-cards', $data);
        $response->assertStatus(201);
        $response->assertSuccessful();
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $debitCard = DebitCard::factory()->for($this->user)->create();
        $response = $this->get('/api/debit-cards/' . $debitCard->id);
        $response->assertStatus(200);
        $response->assertOk();
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $response = $this->get('/api/debit-cards/' . 1);
        $response->assertStatus(404);
        $response->assertNotFound();
    }

    public function testCustomerCanActivateADebitCard()
    {
        $debitCard = DebitCard::factory()->for($this->user)->create();
        $data = [
            'is_active' => true
        ];
        $response = $this->put('/api/debit-cards/' . $debitCard->id, $data);
        $response->assertStatus(200);
        $response->assertOk();
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        $data = [
            'is_active' => false,
        ];
        $debitCard = DebitCard::factory()->for($this->user)->create();
        $response = $this->put('/api/debit-cards/' . $debitCard->id, $data);
        $response->assertStatus(200);
        $response->assertOk();
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        DebitCard::factory()->for($this->user)->create();
        $data = [
            'number' => 342506052519629,
            'type' => "Card",
            'disabled_at' => null,
        ];
        $anotherUserDebitCard = DebitCard::factory()
            ->for(User::factory()->create())
            ->create($data);
        $data = [
            'is_active' => false,
        ];

        $response = $this->put('/api/debit-cards/' . $anotherUserDebitCard->id, $data);
        $response->assertStatus(403);
        $response->assertForbidden();
    }

    public function testCustomerCanDeleteADebitCard()
    {
        $debitCard = DebitCard::factory()->for($this->user)->create([
            'number' => 342506052519629,
            'type' => "Visa",
            'disabled_at' => null,
        ]);

        $response = $this->delete('/api/debit-cards/' . $debitCard->id);
        $response->assertStatus(204);
        $response->assertNoContent();
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        DebitCard::factory()->for($this->user)->create();
        $data = [
            'number' => 5576428580046635,
            'type' => "TestCard",
            'disabled_at' => null,
        ];
        $anotherUserDebitCard = DebitCard::factory()
            ->for(User::factory()->create())
            ->create($data);

        $response = $this->delete('/api/debit-cards/' . $anotherUserDebitCard->id);
        $response->assertStatus(403);
        $response->assertForbidden();
    }

    // Extra bonus for extra tests :)
}
