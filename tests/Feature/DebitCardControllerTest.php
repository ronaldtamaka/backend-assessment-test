<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use Illuminate\Support\Carbon;

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
        $this->actingAs($this->user)
            ->get('/api/debit-cards')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
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
        $otherUser = User::factory()->create();
        DebitCard::factory()->count(3)->for($otherUser)->create();
        $response = $this->get('/api/debit-cards');
        $response->assertStatus(200)->assertOk(); 
    }

    public function testCustomerCanCreateADebitCard()
    {
        $otherUser = User::factory()->create();
        DebitCard::factory()->count(3)->for($otherUser)->create();
        $response = $this->get('/api/debit-cards');

        $response->assertOk();
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
       
        $debitCard = DebitCard::factory()->for($this->user)->create();
        $response = $this->get('/api/debit-cards/' . $debitCard->id);
        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ])
            ->assertJsonMissingExact([
                'expiration_date' => [
                    'date' => $debitCard->expiration_date,
                    'timezone_type' => 3,
                    'timezone' => 'UTC',
                ],
            ]);
     }
    

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
     
        $debitCard = DebitCard::factory()->create();

        $response = $this->get('/api/debit-cards/' . $debitCard->id);
        $response->assertStatus(403);
    }

    public function testCustomerCanActivateADebitCard()
    {
        $debitCard = DebitCard::factory()->for($this->user)->create(['disabled_at' => now()]);
        $response = $this->putJson('/api/debit-cards/' . $debitCard->id, [
            'is_active' =>  true
        ]);
        $response->assertStatus(200);
        $this->assertTrue($debitCard->fresh()->is_active);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        $debitCard = DebitCard::factory()->for($this->user)->create(['disabled_at' => null]);
        $response = $this->putJson('/api/debit-cards/' . $debitCard->id, [
            'is_active' => false,
        ]);
        $response->assertStatus(200);
        $this->assertFalse($debitCard->fresh()->is_active);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $debitCard = DebitCard::factory()->for($this->user)->create();
        $response = $this->putJson('/api/debit-cards/' . $debitCard->id, [
            'is_active' => null,
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['is_active']);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        $debitCard = DebitCard::factory()->for($this->user)->create();
        $response = $this->deleteJson('/api/debit-cards/' . $debitCard->id);
        $response->assertStatus(204);
        $this->assertSoftDeleted('debit_cards', [
            'id' => $debitCard->id,
        ]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        $debitCard = DebitCard::factory()->for($this->user)->create();
        DebitCardTransaction::factory()->for($debitCard)->create();
        $response = $this->deleteJson('/api/debit-cards/' . $debitCard->id);
        $response->assertStatus(403); 
    }
}
