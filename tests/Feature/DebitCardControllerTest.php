<?php

namespace Tests\Feature;
use App\Models\DebitCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Laravel\Passport\HasApiTokens;
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
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        DebitCard::factory(3)->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->get('/api/debit-cards');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'user_id',
                    'number',
                    'type',
                    'expiration_date',
                ]
            ]
        ]);

        $response->assertJsonCount(3, 'data');
    }


    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $token1 = $user1->createToken('test-token-1')->plainTextToken;

        DebitCard::factory(3)->create(['user_id' => $user2->id]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token1",
        ])->get('/api/debit-cards');


        $response->assertStatus(200);

        $response->assertJsonCount(0, 'data');
    }

    public function testCustomerCanCreateADebitCard()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $debitCardData = [
            'type' => 'Visa',

        ];
        $response = $this->post('/api/debit-cards', $debitCardData);
        $response->assertStatus(201);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'type',
            ],
        ]);
        $response->assertJson([
            'data' => [
                'type' => 'Visa',
            ],
        ]);

        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $user->id,
            'type' => 'Visa',

        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);
        $debitCard = DebitCard::factory()->create(['user_id' => $user->id]);

        $response = $this->get("/api/debit-cards/{$debitCard->id}");
        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'type',
            ],
        ]);

        $response->assertJson([
            'data' => [
                'type' => $debitCard->type,
            ],
        ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Passport::actingAs($user1);

        $debitCard = DebitCard::factory()->create(['user_id' => $user2->id]);

        $response = $this->get("/api/debit-cards/{$debitCard->id}");
        $response->assertStatus(403);
    }

    public function testCustomerCanActivateADebitCard()
    {
       $user = User::factory()->create();
        Passport::actingAs($user);
        $debitCard = DebitCard::factory()->create(['user_id' => $user->id, 'is_active' => false]);

        $response = $this->put("/api/debit-cards/{$debitCard->id}", ['is_active' => true]);

        $debitCard->refresh();
        $response->assertStatus(200);
        $this->assertTrue($debitCard->is_active);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);
        $debitCard = DebitCard::factory()->create(['user_id' => $user->id, 'is_active' => true]);
        $response = $this->put("/api/debit-cards/{$debitCard->id}", ['is_active' => false]);

        $debitCard->refresh();
        $response->assertStatus(200);
        $this->assertFalse($debitCard->is_active);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);
        $debitCard = DebitCard::factory()->create(['user_id' => $user->id]);

        $invalidData = [
            'is_active' => 'not_a_boolean',
        ];

        $response = $this->put("/api/debit-cards/{$debitCard->id}", $invalidData);

    }

    public function testCustomerCanDeleteADebitCard()
    {
        
        $user = User::factory()->create();
        Passport::actingAs($user);
        $debitCard = DebitCard::factory()->create(['user_id' => $user->id]);

        $response = $this->delete("/api/debit-cards/{$debitCard->id}");
        $response->assertStatus(204);
        $this->assertDeleted('debit_cards', ['id' => $debitCard->id]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);
        $debitCard = DebitCard::factory()->create(['user_id' => $user->id]);
        $transaction = Transaction::factory()->create(['debit_card_id' => $debitCard->id]);

        $response = $this->delete("/api/debit-cards/{$debitCard->id}");
        $this->assertDatabaseHas('debit_cards', ['id' => $debitCard->id]);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
    }

    // Extra bonus for extra tests :)
}
