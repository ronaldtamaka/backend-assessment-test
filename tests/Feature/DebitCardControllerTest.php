<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\User;
use Carbon\Carbon;
use \Faker\Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    
    protected string $endpoint = "/api/debit-cards";

    protected Generator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->generator = \Faker\Factory::create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        // get /debit-cards

        // create 3 dCards and auto asssign to the current user.
        $dCards = DebitCard::factory()->count(2)->for($this->user)->create();

        /**
         * @var \App\Models\DebitCard $firstUserDCards
         */
        $firstUserDCards = $dCards->first();

        // Testing api call and asserting schenario.
        $res = $this->getJson($this->endpoint);

        // Asserting Response Ok.
        $res->assertOk();

        // Asserting Database Cheecking.
        $this->assertDatabaseHas("debit_cards", [
            "type" => $firstUserDCards->type
        ], "mysql");

        // Asserting thruthiness & policy cheecking.
        $this->assertTrue($this->user->can("view", $firstUserDCards));
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        /**
         * @var \App\Models\User $anotherUser
         */
        $anotherUser = User::factory()->create();

        // create a D cards for this user
        $userDCards = DebitCard::factory()->for($this->user)->create();

        // create a D cards for other user
        $anotherUserDCards = DebitCard::factory()->for($anotherUser)->create();

        // Testing api call and asserting schenario.
        $res = $this->getJson($this->endpoint);

        // Asserting Response Ok.
        $res->assertOk();

        // Asserting Database Cheecking.
        $this->assertDatabaseHas("debit_cards", [
            "type" => $userDCards->type
        ], "mysql");
        $this->assertDatabaseHas("debit_cards", [
            "type" => $anotherUserDCards->type
        ], "mysql");

        // assertion for policy, schenario 1, 
        // [user] can see they respective d Cards but can not see [another] user d cards
        $this->actingAs($this->user)->assertTrue($this->user->can("view", $userDCards));
        $this->actingAs($this->user)->assertTrue($this->user->cannot("view", $anotherUserDCards));

        // assertion for policy, schenario 2, 
        // [Another user] can see they respective d Cards but can not see [user] d cards
        $this->actingAs($anotherUser)->assertTrue($anotherUser->can("view", $anotherUserDCards));
        $this->actingAs($anotherUser)->assertTrue($anotherUser->cannot("view",$userDCards));
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $fakeDebitCardsData = [
            'number' => $this->generator->creditCardNumber,
            'type' => $this->generator->creditCardType,
            'expiration_date' => $this->generator->dateTimeBetween('+1 month', '+3 year'),
            'disabled_at' => $this->generator->boolean ? $this->generator->dateTime : null,
            'user_id' => fn () => $this->user,
        ];
        $res = $this->postJson($this->endpoint, $fakeDebitCardsData);

        $res->assertStatus(201);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        // create a debit cards 
        $fakeDebitCardsData = $this->user->debitCards()->create([
            'number' => $this->generator->creditCardNumber,
            'type' => $this->generator->creditCardType,
            'expiration_date' => $this->generator->dateTimeBetween('+1 month', '+3 year'),
            'disabled_at' => $this->generator->boolean ? $this->generator->dateTime : null,
            'user_id' => fn () => $this->user,
        ]);
        $dCardsID = $fakeDebitCardsData->id;

        $res = $this->getJson($this->endpoint . "/" . $dCardsID);

        $res->assertOk();
        $this->assertDatabaseHas("debit_cards", $fakeDebitCardsData->toArray());
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $res = $this->getJson($this->endpoint . "/" . 100);

        $res->assertStatus(404);
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        // create in-active d cards first
        $inActiveDacards = DebitCard::factory()->for($this->user)->expired()->create();
        $this->assertDatabaseHas("debit_cards", $inActiveDacards->toArray());
        $this->assertFalse($inActiveDacards->isActive);

        $res = $this->putJson($this->endpoint . "/" . $inActiveDacards->id, [
            "is_active" => true
        ]);
        $res->assertOk();
        $updated = DebitCard::where(["id" => $inActiveDacards->id])->first();
        $this->assertTrue($updated->isActive);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        // create active d cards first
        $activeDacards = DebitCard::factory()->for($this->user)->active()->create();
        $this->assertDatabaseHas("debit_cards", $activeDacards->toArray());
        $this->assertTrue($activeDacards->isActive);

        $res = $this->putJson($this->endpoint . "/" . $activeDacards->id, [
            "is_active" => false
        ]);
        $res->assertOk();
        $updated = DebitCard::where(["id" => $activeDacards->id])->first();
        $this->assertFalse($updated->isActive);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $dCards = DebitCard::factory()->for($this->user)->create();
        $res = $this->putJson($this->endpoint . "/" . $dCards->id, []);
        $res->assertStatus(422); // validation erorr
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $dCards = DebitCard::factory()->for($this->user)->create();
        $res = $this->deleteJson($this->endpoint . "/" . $dCards->id);
        $res->assertStatus(204);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $dCards = DebitCard::factory()->for($this->user)->create();
        $res = $this->deleteJson($this->endpoint . "/" . $dCards->id);
        $res->assertStatus(204);
    }

    // Extra bonus for extra tests :)
}
