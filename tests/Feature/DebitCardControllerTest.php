<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DebitCard;
use Faker\Factory;
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
        $this->user = Passport::actingAs(User::factory()->create());
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        // get /debit-cards
        DebitCard::factory()->count(3)->for($this->user)->create();
        $this->get('/api/debit-cards')
            ->assertOk();
        $this->assertDatabaseCount('debit_cards', 3);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        DebitCard::factory()->count(3)->for($this->user)->create();

        $customer = Passport::actingAs(User::factory()->create());
        $this->actingAs($customer);

        $res = $this->get('/api/debit-cards');
        $this->assertTrue(count($res->decodeResponseJson()->json()) === 0);
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $res = $this->post('/api/debit-cards', [
            'type' => 'random'
        ]);

        $json = $res->decodeResponseJson()->json();
        
        $res->assertCreated();
        $this->assertDatabaseHas('debit_cards', [
            'id' => $json['id'],
        ]);
    }

    public function testCustomerCanCreateADebitCardInvalid()
    {
        // post /debit-cards
        $this->post('/api/debit-cards', [
            'type' => ''
        ])->assertSessionHasErrors(['type']);;

        $this->post('/api/debit-cards', [
            'type' => 1
        ])->assertSessionHasErrors(['type']);;
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $debit = DebitCard::factory()->for($this->user)->create();
        $this->get('/api/debit-cards/' . $debit->id)
            ->assertOk();
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $this->get('/api/debit-cards/' . 99999)
        ->assertNotFound();
    }

    function setDebitCardStatusForTest(bool $status) {
        $debit = DebitCard::factory()->for($this->user)->create();
        $this->put('/api/debit-cards/' . $debit->id,  [
            'is_active' => $status
        ])->assertOk();

        $this->assertDatabaseMissing('debit_cards', [
            'id' => $debit->id,
            'is_active' => null
        ]);
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $this->setDebitCardStatusForTest(true);
    }
    
    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $this->setDebitCardStatusForTest(false);
    }
    
    public function testForbiddenCustomerCanUpdateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debit = DebitCard::factory()->for(User::factory()->create())->create();
        $this->put('/api/debit-cards/' . $debit->id,  [
            'is_active' => false
        ])->assertForbidden();
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $debit = DebitCard::factory()->for($this->user)->create();
        $this->put('/api/debit-cards/' . $debit->id, [])->assertSessionHasErrors(['is_active']);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $debit = DebitCard::factory()->for($this->user)->create();
        $this->delete('/api/debit-cards/' . $debit->id)->assertNoContent();
        $this->assertSoftDeleted('debit_cards', [
            'id' => $debit->id,
        ]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $debit = DebitCard::factory()->for(User::factory()->create())->create();
        $this->delete('/api/debit-cards/' . $debit->id)->assertForbidden();
    }

    // Extra bonus for extra tests :)
}
