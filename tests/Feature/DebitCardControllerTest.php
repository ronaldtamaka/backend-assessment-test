<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\DebitCard;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Current User Model
     *
     * @var User
     */
    protected User $user;

    /**
     * Set up method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
    }

    /**
     * Test Customer Can See a List of Debit Cards
     *
     * @return void
     */
    public function testCustomerCanSeeAListOfDebitCards(): void
    {
        DebitCard::factory()->active()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson('/api/debit-cards');
        $response
            ->assertOk()
            ->assertJsonCount(1);

    }

    /**
     * Test Customer Cannot See a List of Debit Cards of Other Customers
     *
     * @return void
     */
    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers(): void
    {
        $newUser = User::factory()->create();
        DebitCard::factory()->active()->create([
            'user_id' => $newUser->id
        ]);

        $response = $this->getJson('/api/debit-cards');
        $response
            ->assertOk()
            ->assertJsonCount(0);
    }

    /**
     * Test Customer Can Create a Debit Card
     *
     * @return void
     */
    public function testCustomerCanCreateADebitCard(): void
    {
        $response = $this->postJson('/api/debit-cards');
        $response
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['type']);
        
        $response = $this->postJson('/api/debit-cards', [
            'type' => $this->faker->creditCardType,
        ]);
        $response
            ->assertCreated();

        $this->assertDatabaseHas('debit_cards', [
            'id' => $response->json('id'),
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Test Customer Can See a Single Debit Card Details
     *
     * @return void
     */
    public function testCustomerCanSeeASingleDebitCardDetails(): void
    {
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson("/api/debit-cards/{$debitCard->id}");
        $response
            ->assertOk();

        $this->assertDatabaseHas('debit_cards', [
            'id' => $response->json('id'),
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Test Customer Cannot See s Single Debit Card Details
     *
     * @return void
     */
    public function testCustomerCannotSeeASingleDebitCardDetails(): void
    {
        $user = User::factory()->create();
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $user->id
        ]);

        $response = $this->getJson("/api/debit-cards/{$debitCard->id}");
        $response
            ->assertForbidden();
    }

    /**
     * Test Customer Can Activate a Debit Card
     *
     * @return void
     */
    public function testCustomerCanActivateADebitCard(): void
    {
        $debitCard = DebitCard::factory()->expired()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", [
            'is_active' => true
        ]);
        $response
            ->assertOk();

        $model = $this->user->debitCards()->active()->where('id', $response->json('id'))->first();

        $this->assertTrue(!empty($model));
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
