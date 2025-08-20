<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
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
        
        DebitCard::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'disabled_at' => null, 
        ]);

        
        $otherUser = User::factory()->create();
        DebitCard::factory()->count(2)->create([
            'user_id' => $otherUser->id,
            'disabled_at' => null,
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
                             'is_active',
                         ]
                     ]
                 ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        
        $otherUser = User::factory()->create();
        DebitCard::factory()->count(5)->create([
            'user_id' => $otherUser->id,
            'disabled_at' => null,
        ]);

        
        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200)
                 ->assertJsonCount(0, 'data');
    }

    public function testCustomerCanCreateADebitCard()
    {
        $debitCardData = [
            'type' => 'mastercard',
        ];

        $response = $this->postJson('/api/debit-cards', $debitCardData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'number',
                         'type',
                         'expiration_date',
                         'is_active',
                     ]
                 ])
                 ->assertJson([
                     'data' => [
                         'type' => 'mastercard',
                         'is_active' => true,
                     ]
                 ]);

        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
            'type' => 'mastercard',
            'disabled_at' => null,
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'disabled_at' => null,
        ]);

        $response = $this->getJson('/api/debit-cards/' . $debitCard->id);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'number',
                         'type',
                         'expiration_date',
                         'is_active',
                     ]
                 ])
                 ->assertJson([
                     'data' => [
                         'id' => $debitCard->id,
                         'number' => $debitCard->number,
                         'type' => $debitCard->type,
                     ]
                 ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        
        $otherUser = User::factory()->create();
        $debitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id,
            'disabled_at' => null,
        ]);

        $response = $this->getJson('/api/debit-cards/' . $debitCard->id);

        $response->assertStatus(403);
    }

    public function testCustomerCanActivateADebitCard()
    {
        
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => false,
            'disabled_at' => now(),
        ]);

        $response = $this->putJson('/api/debit-cards/' . $debitCard->id, [
            'is_active' => true,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'data' => [
                         'id' => $debitCard->id,
                         'is_active' => true,
                     ]
                 ]);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'disabled_at' => null,
        ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'disabled_at' => null,
        ]);

        $response = $this->putJson('/api/debit-cards/' . $debitCard->id, [
            'is_active' => false,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'data' => [
                         'id' => $debitCard->id,
                         'is_active' => false,
                     ]
                 ]);

        $debitCard->refresh();
        $this->assertNotNull($debitCard->disabled_at);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
        ]);

        
        $response = $this->putJson('/api/debit-cards/' . $debitCard->id, []);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['is_active']);

        
        $response = $this->putJson('/api/debit-cards/' . $debitCard->id, [
            'is_active' => 'invalid',
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['is_active']);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson('/api/debit-cards/' . $debitCard->id);

        $response->assertStatus(204);
        
        $this->assertSoftDeleted('debit_cards', [
            'id' => $debitCard->id,
        ]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
        ]);

        
        DebitCardTransaction::factory()->create([
            'debit_card_id' => $debitCard->id,
        ]);

        $response = $this->deleteJson('/api/debit-cards/' . $debitCard->id);

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'deleted_at' => null,
        ]);
    }

    
    
    public function testCustomerCannotCreateDebitCardWithoutType()
    {
        $response = $this->postJson('/api/debit-cards', []);
        
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['type']);
    }
    
    public function testCustomerCannotUpdateOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $debitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id,
        ]);
        
        $response = $this->putJson('/api/debit-cards/' . $debitCard->id, [
            'is_active' => false,
        ]);
        
        $response->assertStatus(403);
    }
    
    public function testCustomerCannotDeleteOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $debitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id,
        ]);
        
        $response = $this->deleteJson('/api/debit-cards/' . $debitCard->id);
        
        $response->assertStatus(403);
    }
    
    public function testOnlyActiveDebitCardsAreListedInIndex()
    {
        
        DebitCard::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'disabled_at' => null,
        ]);
        
        
        DebitCard::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'is_active' => false,
            'disabled_at' => now(),
        ]);
        
        $response = $this->getJson('/api/debit-cards');
        
        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }
}
