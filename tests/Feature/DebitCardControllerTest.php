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
        DebitCard::factory(5)->create(['user_id' => $this->user->id]);

        $response = $this->get('/api/debit-cards');

        $response->assertStatus(200)
            ->assertJsonStructure([
                [
                    'id',
                    'number',
                    'type',
                    'expiration_date',
                    'is_active',
                ]
            ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        DebitCard::factory(5)->for($this->user)->create();

        $anotherUser = User::factory()->create();
        Passport::actingAs($anotherUser);

        $response = $this->get('/api/debit-cards');

        $response->assertStatus(200)->assertJsonStructure([]);
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $data = [
            'type' => 'visa',
        ];

        $response = $this->post('/api/debit-cards', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $debitCard = $this->user->debitCards()->create([
            'type' => 'visa',
            'number' => '1234567890123456',
            'expiration_date' => now()->addYear(),
        ]);

        $response = $this->get("/api/debit-cards/$debitCard->id");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $debitCard = $this->user->debitCards()->create([
            'type' => 'visa',
            'number' => '1234567890123456',
            'expiration_date' => now()->addYear(),
        ]);

        $anotherUser = User::factory()->create();
        Passport::actingAs($anotherUser);

        $response = $this->get("/api/debit-cards/$debitCard->id");

        $response->assertStatus(403)->assertForbidden();
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = $this->user->debitCards()->create([
            'type' => 'visa',
            'number' => '1234567890123456',
            'expiration_date' => now()->addYear(),
        ]);

        $response = $this->put("/api/debit-cards/$debitCard->id", ['is_active' => true]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = $this->user->debitCards()->create([
            'type' => 'visa',
            'number' => '1234567890123456',
            'expiration_date' => now()->addYear(),
        ]);

        $response = $this->put("/api/debit-cards/$debitCard->id", ['is_active' => false]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ]);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = $this->user->debitCards()->create([
            'type' => 'visa',
            'number' => '1234567890123456',
            'expiration_date' => now()->addYear(),
        ]);

        $response = $this->put("/api/debit-cards/$debitCard->id", ['is_active' => 'not_boolean_value']);

        $response->assertStatus(302);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = $this->user->debitCards()->create([
            'type' => 'visa',
            'number' => '1234567890123456',
            'expiration_date' => now()->addYear(),
        ]);

        $response = $this->delete("/api/debit-cards/$debitCard->id");

        $response->assertStatus(204);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = $this->user->debitCards()->create([
            'type' => 'visa',
            'number' => '1234567890123456',
            'expiration_date' => now()->addYear(),
        ]);

        $anotherUser = User::factory()->create();
        Passport::actingAs($anotherUser);

        $response = $this->delete("/api/debit-cards/$debitCard->id");

        $response->assertStatus(403)->assertForbidden();
    }

    // Extra bonus for extra tests :)

    public function testCustomerCannotFoundASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $response = $this->get("/api/debit-cards/1");

        $response->assertStatus(404)->assertNotFound();
    }

}
