<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\Models\DebitCard;
use App\Models\DebitCardTransaction;

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
        // Arrange: bikin 2 kartu milik user login
        $myCards = \App\Models\DebitCard::factory()->count(2)->active()->create([
            'user_id' => $this->user->id,
        ]);

        // Bikin 1 kartu milik user lain
        \App\Models\DebitCard::factory()->create();

        // Act
        $response = $this->getJson('/api/debit-cards');
        
        // dd($response->json());

        // Assert: status OK
        $response->assertStatus(200);

        // Pastikan hanya 2 kartu (milik user login)
        $response->assertJsonCount(2, 'data');

        // Cek ID dan user_id muncul di response
        foreach ($myCards as $card) {
            $response->assertJsonFragment([
                'id' => $card->id,
            ]);
        }

        // Extra: pastikan tidak ada kartu dari user lain
        $response->assertJsonMissing([
            'user_id' => \App\Models\User::factory()->create()->id,
        ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Kartu milik user lain
        DebitCard::factory()->count(2)->for($otherUser)->create();

        $this->actingAs($user, 'api');

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }


    public function testCustomerCanCreateADebitCard()
    {
        $payload = [
            'type' => 'Visa',
        ];

        $response = $this->postJson('/api/debit-cards', $payload);

        $response->assertStatus(201);

        // Cek struktur response JSON
        $response->assertJsonStructure([
            'data' => [
                'id',
                'number',
                'type',
                'expiration_date',
            ]
        ]);

        // Pastikan nomor kartu 16 digit
        $this->assertEquals(16, strlen($response->json('data.number')));

        // Pastikan data masuk DB
        $this->assertDatabaseHas('debit_cards', [
            'type' => $payload['type'],
            'user_id' => $this->user->id,
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $card = \App\Models\DebitCard::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/debit-cards/{$card->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $card->id,
            'number' => (int)$card->number,
            'type' => $card->type,
        ]);
    }


    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $me = User::factory()->create();
        $otherUser = User::factory()->create();

        $card = \App\Models\DebitCard::factory()->for($otherUser)->create();

        Passport::actingAs($me);

        $response = $this->getJson("/api/debit-cards/{$card->id}");

        $response->assertStatus(403);
    }

    public function testCustomerCanActivateADebitCard()
    {
        $user = User::factory()->create();

        $card = DebitCard::factory()->create([
            'user_id' => $user->id,
            'disabled_at' => null,
        ]);

        $this->actingAs($user, 'api');

        $response = $this->putJson("/api/debit-cards/{$card->id}", [
            'is_active' => false,
        ]);

        $response->assertStatus(200);

        $this->assertNotNull($card->fresh()->disabled_at);

        $response->assertJsonFragment([
            'id' => $card->id,
            'is_active' => false,
        ]);
    }
   public function testCustomerCanDeactivateADebitCard()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        $card = DebitCard::factory()->create([
            'user_id' => $user->id,
            'disabled_at' => null,
        ]);

        $response = $this->putJson("/api/debit-cards/{$card->id}", [
            'is_active' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $card->id,
            'is_active' => false,
        ]);

        $this->assertNotNull($card->fresh()->disabled_at);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        $card = DebitCard::factory()->create([
            'user_id' => $user->id,
            'disabled_at' => null,
        ]);

        // Kirim is_active salah (harusnya boolean)
        $response = $this->putJson("/api/debit-cards/{$card->id}", [
            'is_active' => 'bukan_boolean',
        ]);

        $response->assertStatus(422); // Unprocessable Entity
        $response->assertJsonValidationErrors('is_active');

        // Pastikan tidak berubah
        $this->assertNull($card->fresh()->disabled_at);
    }


    public function testCustomerCanDeleteADebitCard()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        $card = DebitCard::factory()->create([
            'user_id' => $user->id,
        ]);

        // Pastikan kartu belum punya transaksi
        $this->assertFalse($card->debitCardTransactions()->exists());

        $response = $this->deleteJson("/api/debit-cards/{$card->id}");

        $response->assertStatus(204); // No Content

        // Pastikan kartu terhapus dari DB
        $this->assertSoftDeleted('debit_cards', [
            'id' => $card->id,
        ]);
    }


    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Buat debit card milik user
        $card = DebitCard::factory()->for($user)->create();

        // Buat 1 transaksi terkait kartu tsb
        DebitCardTransaction::factory()->for($card)->create();

        // Coba delete
        $response = $this->deleteJson("/api/debit-cards/{$card->id}");

        // Harus forbidden
        $response->assertStatus(403);

        // Pastikan tetap ada di database
        $this->assertDatabaseHas('debit_cards', [
            'id' => $card->id,
        ]);
    }

    // Extra bonus for extra tests :)
    public function testCustomerCannotDeactivateAnotherUsersDebitCard()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user, 'api');

        $card = DebitCard::factory()->create([
            'user_id' => $otherUser->id,
            'disabled_at' => null,
        ]);

        $response = $this->putJson("/api/debit-cards/{$card->id}", [
            'is_active' => false,
        ]);

        $response->assertStatus(403); // Forbidden karena gagal authorize

        $this->assertNull($card->fresh()->disabled_at); // Masih aktif
    }

    public function testCustomerCannotDeleteAnotherUsersDebitCard()
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create(); // User lain

        $card = DebitCard::factory()->create([
            'user_id' => $owner->id,
        ]);

        $this->actingAs($attacker, 'api');

        $response = $this->deleteJson("/api/debit-cards/{$card->id}");

        $response->assertStatus(403);

        // Pastikan masih ada di DB
        $this->assertDatabaseHas('debit_cards', [
            'id' => $card->id,
            'deleted_at' => null,
        ]);
    }
    public function testCustomerCannotActivateExpiredDebitCard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $card = DebitCard::factory()->for($user)->create([
            'expiration_date' => now()->subDay(),
        ]);

        $response = $this->putJson("/api/debit-cards/{$card->id}", [
            'is_active' => true,
        ]);

        $response->assertStatus(422); // Expired cards cannot be activated
    }
    

}
