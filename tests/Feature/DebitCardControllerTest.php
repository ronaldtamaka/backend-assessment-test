<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use Faker\Factory;
use App\Http\Resources\DebitCardResource;

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
        DebitCard::factory()->create(["user_id"=>$this->user->id]);
        $this->getJson('api/debit-cards')
            ->assertOk()
            ->assertJsonStructure(
                [
                    '*'=>['id','number','type','expiration_date','is_active']
                ]
            );
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        $user = User::factory()->create();
        DebitCard::factory()->create(["user_id"=>$this->user->id]);
        DebitCard::factory()->create(["user_id"=>$user->id]);

        $dc = $this->user->debitCards()
            ->active()
            ->get();

        $this->getJson('api/debit-cards')
            ->assertOk()
            ->assertJson(json_decode(json_encode(DebitCardResource::collection($dc)),true));

    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $ccType = Factory::create()->creditCardType;

        $this->postJson('api/debit-cards', ["type"=>$ccType])
            ->assertCreated()
            ->assertJsonStructure(['id','number','type','expiration_date','is_active']);

        $this->assertDatabaseHas('debit_cards', ['type' => $ccType]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $dc = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $this->getJson("api/debit-cards/$dc->id")
            ->assertOk()
            ->assertJsonStructure(['id','number','type','expiration_date','is_active']);

        $this->assertDatabaseHas('debit_cards', ['id' => $dc->id]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $user = User::factory()->create();

        $dc = DebitCard::factory()->create(['user_id' => $user->id]);


        $this->getJson("api/debit-cards/$dc->id")
            ->assertStatus(403);
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}

        $dc = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $this->putJson("api/debit-cards/$dc->id", ['is_active' => true])
            ->assertOk()
            ->assertJsonStructure(['id','number','type','expiration_date','is_active']);

    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $dc = \App\Models\DebitCard::factory()->create(['user_id' => $this->user->id]);

        $this->putJson("api/debit-cards/$dc->id", ['is_active' => false])
            ->assertOk()
            ->assertJsonStructure(['id','number','type','expiration_date','is_active']);

        
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $dc = \App\Models\DebitCard::factory()->create(['user_id' => $this->user->id]);

        $this->putJson("api/debit-cards/$dc->id",['is_active' => null])
            ->assertStatus(422)
            ->assertJsonStructure(['message','errors'=>['is_active']]);

    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $dc = DebitCard::factory()->create(['user_id' => $this->user->id,]);

        $this->delete('api/debit-cards/' . $dc->id)
            ->assertNoContent(); 
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $dc = DebitCardTransaction::factory()->create();  

        $this->delete('api/debit-cards/' . $dc->debit_card_id)
            ->assertStatus(403);

    }

    // Extra bonus for extra tests :)
}
