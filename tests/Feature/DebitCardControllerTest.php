<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Carbon\Carbon;

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
        DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'user_id' => $this->user->id,
                        'number' => '012345',
                        'type' => 'visa',
                        'expiration_date' => Carbon::now(),
                        'is_active' => true,
                    ],
                ],
            ]);
        
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        DebitCard::factory()->create(); // DebitCard for another user

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $response = $this->postJson('api/debit-cards', ['type' => 'visa']);

        $response->assertStatus(201)
            ->assertJson([
                'user_id' => $this->user->id,
                'number' => random_int(100000, 999999), 
                'type' => 'visa',
                'expiration_date' => Carbon::now(), 
                'is_active' => true,
            ]);

        $this->assertCount(1, DebitCard::all());
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("api/debit-cards/{$debitCard->id}");

        $response->assertStatus(200)
            ->assertJson([
                'user_id' => $this->user->id,
                'number' => $debitCard->number, 
                'type' => $debitCard->type,
                'expiration_date' => Carbon::now(), 
                'is_active' => true,
            ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(); // DebitCard for another user

        $response = $this->getJson("api/debit-cards/{$debitCard->id}");

        $response->assertStatus(404);
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id, 
            'disabled_at' => now()
        ]);

        $response = $this->putJson("api/debit-cards/{$debitCard->id}", ['is_active' => true]);

        $response->assertStatus(200)
            ->assertJson([
                'user_id' => $this->user->id,
                'number' => $debitCard->number, 
                'type' => $debitCard->type,
                'expiration_date' => Carbon::now(), 
                'is_active' => true,
            ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->putJson("api/debit-cards/{$debitCard->id}", ['is_active' => false]);

        $response->assertStatus(200)
            ->assertJson([
                'user_id' => $this->user->id,
                'number' => $debitCard->number, 
                'type' => $debitCard->type,
                'expiration_date' => Carbon::now(), 
                'is_active' => false,
            ]);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        // Lakukan permintaan untuk mengubah kartu debit dengan validasi yang salah
        $response = $this->putJson("api/debit-cards/{$debitCard->id}", ['is_active' => 'invalid_value']);

        // Harapannya adalah status gagal validasi (422)
        $response->assertStatus(422);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        // Lakukan permintaan untuk menghapus kartu debit
        $response = $this->deleteJson("api/debit-cards/{$debitCard->id}");
    
        // Harapannya adalah status sukses tanpa konten (204)
        $response->assertStatus(204);
    
        // Pastikan kartu debit telah dihapus dari database
        $this->assertSoftDeleted('debit_cards', ['id' => $debitCard->id]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);
        $transaction = DebitCardTransaction::factory()->create(['debit_card_id' => $debitCard->id]);

        // Lakukan permintaan untuk menghapus kartu debit yang memiliki transaksi
        $response = $this->deleteJson("api/debit-cards/{$debitCard->id}");

        // Harapannya adalah status gagal (403) karena kartu debit memiliki transaksi yang belum diselesaikan
        $response->assertStatus(403);
    }

    // Extra bonus for extra tests :)
}
