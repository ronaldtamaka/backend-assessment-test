<?php

namespace Tests\Feature;

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
        $debitCards = DebitCard::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'number',
                        'type',
                        'expiration_date',
                        'is_active'
                    ]
                ]
            ]);

        foreach ($response->json('data') as $card) {
            $this->assertEquals($this->user->id, DebitCard::find($card['id'])->user_id);
        }
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $otherUser = User::factory()->create();
        
        $ownDebitCards = DebitCard::factory()->count(2)->create([
            'user_id' => $this->user->id
        ]);

        $otherDebitCards = DebitCard::factory()->count(3)->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $returnedCardIds = collect($response->json('data'))->pluck('id');
        foreach ($ownDebitCards as $card) {
            $this->assertTrue($returnedCardIds->contains($card->id));
        }
        foreach ($otherDebitCards as $card) {
            $this->assertFalse($returnedCardIds->contains($card->id));
        }
    }

    public function testCustomerCanCreateADebitCard()
    {
        $debitCardData = [
            'type' => 'visa',
            'expiration_date' => now()->addYears(3)->format('Y-m-d'),
        ];

        $response = $this->postJson('/api/debit-cards', $debitCardData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'number',
                    'type',
                    'expiration_date',
                    'is_active'
                ]
            ]);

        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
            'type' => 'visa',
            'is_active' => true
        ]);

        // Verify card number is generated
        $createdCard = DebitCard::where('user_id', $this->user->id)->first();
        $this->assertNotNull($createdCard->number);
        $this->assertEquals(16, strlen($createdCard->number));
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
