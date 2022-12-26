<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DebitCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Carbon\Carbon; 
use Illuminate\Http\Client\Response;

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
        $response = $this->get('/debit-cards')
                ->assertJsonStructure([
                    'status',
                    'result' => [
                        'number',
                        'type',
                        'expiration_date',
                    ],
                ]);
        $response->assertStatus(200);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        $response = $this->get('/debit-cards')
                ->assertFalse();
                
        $debitCards = (array)json_decode($this->response->content());
        $response->assertContains($this->user->id, $debitCards['user_id']);
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $this->post('/debit-cards', [
            'user_id' => $this->user->id,
            'type' => 'type 1',
            'number' => rand(1000000000000000, 9999999999999999),
            'expiration_date' => Carbon::now()->addYear(),
        ]);

        $newDebitCard = (array)json_decode($this->response->content());

        $this->assertArrayHasKey('user_id', $newDebitCard);
        $this->assertCount(
            1,
            $this->user->debitCards->latest()->count(), "1 data has been added"
        );
        $this->assertEquals($newDebitCard['number'], $this->user->debitCards->get());
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $debitCardId = 1;
        $response = $this->get('api/debit-cards', [
            'id' => $debitCardId,
        ])->assertJsonStructure([
            'status',
            'result' => [
                'number',
                'type',
                'expiration_date',
            ],
        ]);
        
        $response->assertSuccessful();
        $response->assertSee($this->user->id, "data has found");
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $debitCardId = 1;
        $response = $this->get('/debit-cards', [
            'id' => $debitCardId,
        ]);
        
        $debitCards = (array)json_decode($this->response->content());
        $response->assertNull( 
            $debitCards, 
            "Data not available"
        ); 
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCardId = 1;
        $response = $this->put('/debit-cards', [
            'disabled_at' => Carbon::now()->addYear()
        ], [
            'id' => $debitCardId
        ]);

        $response->assertStatus(200);

        $debitCardActivated = (array)json_decode($this->response->content());
        $response->assertNotNull($debitCardActivated['disabled_at'], "Debit card has been activated");
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCardId = 1;
        $response = $this->put('api/debit-cards', [
            'disabled_at' => NULL
        ], [
            'id' => $debitCardId
        ]);

        $response->assertStatus(200);

        $debitCardDeactivated = (array)json_decode($this->response->content());
        $response->assertNull($debitCardDeactivated['disabled_at'], "Debit card has been deactivated");
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $debitCardId = 1;
        $response = $this->put('api/debit-cards', [
            'disabled_at' => NULL
        ], [
            'id' => $debitCardId
        ]);


        $response->assertValid(['disabled_at']);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $debitCardData =
        [
            'user_id' => $this->user->id,
            'type' => 'type 1',
            'number' => rand(1000000000000000, 9999999999999999),
            'expiration_date' => Carbon::now()->addYear(),
        ];
        $debitCard = DebitCard::create(
            $debitCardData
        );

        $response = $this->delete('api/debit-cards', [
            'id' => $debitCard->id,
        ]);

        $response->assertStatus(Response::HTTP_NO_CONTENT);
        $response->assertJsonStructure(['error']);
        $response->assertDatabaseMissing('debit_cards', $debitCardData);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $debitCardData =
        [
            'user_id' => $this->user->id,
            'type' => 'type 1',
            'number' => rand(1000000000000000, 9999999999999999),
            'expiration_date' => Carbon::now()->addYear(),
        ];
        $debitCard = DebitCard::create(
            $debitCardData
        );

        $response = $this->delete('api/debit-cards', [
            'id' => 1,
        ]);

        $response->assertStatus(400);
    }

    // Extra bonus for extra tests :)
    public function testCustomerCannotCreateADebitCard()
    {
        // post /debit-card-transactions
        $response = $this->post('/debit-card-transactions', [
            'user_id' => $this->user->id,
            'type' => 1,
            'number' => rand(1000000000000000, 9999999999999999),
            'expiration_date' => Carbon::now()->addYear(),
        ]);

        $newDebitCardTransaction = $response->request->getContent();
        $getDebitCard = $this->debitCard->where(['id'=> $newDebitCardTransaction['debit_card_id']])->get();

        $response->assertInvalid(['user_id', 'type', 'number', 'expiration_date']);
    }
}
