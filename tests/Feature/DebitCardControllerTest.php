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

    // GET /debit-cards

    public function testCustomerCanSeeAListOfOwnDebitCards()
    {
        $debitCards = DebitCard::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->json('GET', '/debit-cards/list'); // Gunakan metode 'json' untuk permintaan API

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');

        foreach ($debitCards as $debitCard) {
            $response->assertJsonPath('data.*.id', $debitCard->id);
        }
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        DebitCard::factory()->create(); // Create a card for a different user

        $response = $this->json('GET', '/debit-cards/list'); // Gunakan metode 'json' untuk permintaan API

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }

    // POST /debit-cards
    
    public function testCustomerCanCreateADebitCard()
    {
        $data = [
            'number' => '1234567812345678',
            'type' => 'debit',
            'expiration_date' => '12/25',
        ];

        $response = $this->json('POST', '/debit-cards/create', $data); // Gunakan metode 'json' untuk permintaan API

        $response->assertStatus(201);
        $this->assertDatabaseHas('debit_cards', $data);

        $debitCard = DebitCard::latest()->first();
        $response->assertJsonPath('data.id', $debitCard->id);
        $response->assertJsonPath('data.number', $data['number']);
        $response->assertJsonPath('data.type', $data['type']);
        $response->assertJsonPath('data.expiration_date', $data['expiration_date']);
    }

    // ... implementasi metode uji coba lainnya sesuai deskripsi ...

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);
        $debitCard->debitCardTransactions()->create(['amount' => 100, 'currency_code' => 'USD']);

        $response = $this->json('DELETE', '/debit-cards/' . $debitCard->id); // Gunakan metode 'json' untuk permintaan API

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Debit card cannot be deleted because it has transactions.',
        ]);
    }

    // Extra bonus for extra tests :)
}
