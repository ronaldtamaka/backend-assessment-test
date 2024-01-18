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

        // Arrange
        DebitCard::factory()->count(3)->for($this->user)->active()->create();

        // Act
        $response = $this->get('/api/debit-cards');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            [
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ],
        ]);
        $this->assertCount(3, $response->json());
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards

        // Arrange
        $otherCustomer = User::factory()->create();
        DebitCard::factory()->count(3)->for($otherCustomer)->active()->create();
       
        // Act
        $response = $this->get('/api/debit-cards');

        // Assert
        $response->assertStatus(200);
        $this->assertCount(0, $response->json());
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards

        // Arrange
        $data = [
            'type' => 'visa',
        ];
     
        // Act
        $response = $this->post('/api/debit-cards', $data);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure([
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

        // Arrange
        $debitCard = DebitCard::factory()->for($this->user)->active()->create();

        // Act
        $response = $this->get("/api/debit-cards/{$debitCard->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
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

        // Arrange
        $otherCustomer = User::factory()->create();
        $debitCard = DebitCard::factory()->for($otherCustomer)->create();

        // Act
        $response = $this->get("/api/debit-cards/{$debitCard->id}");

        // Assert
        $response->assertStatus(403);
        
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}

        // Arrange
        $data = [
            'is_active' => true,
        ];
        $debitCard = DebitCard::factory()->for($this->user)->expired()->create();

        // Act
        $response = $this->put("/api/debit-cards/{$debitCard->id}", $data);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'number',
            'type',
            'expiration_date',
            'is_active',
        ]);
        $this->assertTrue($response->json('is_active'));
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}

        // Arrange
        $data = [
            'is_active' => false,
        ];
        $debitCard = DebitCard::factory()->for($this->user)->active()->create();

        // Act
        $response = $this->put("/api/debit-cards/{$debitCard->id}", $data);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'number',
            'type',
            'expiration_date',
            'is_active',
        ]);
        $this->assertFalse($response->json('is_active'));

    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}

        // Arrange
        $data = [
            'is_active' => 'wrong',
        ];
        $debitCard = DebitCard::factory()->for($this->user)->active()->create();

        // Act
        $response = $this->put("/api/debit-cards/{$debitCard->id}", $data);
       
        // Assert
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'is_active' => 'The is active field must be true or false.',
        ]);
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
