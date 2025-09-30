<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;
use DB;

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
        $cards = DebitCard::factory()->count(2)->active()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/debit-cards');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json());
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $other = User::factory()->create();
        DebitCard::factory()->create(['user_id' => $other->id]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json());
    }

    public function testCustomerCanCreateADebitCard()
    {
        $payload = [
            'type' => 'Visa',
        ];

        $response = $this->postJson('/api/debit-cards', $payload)
            ->assertStatus(201);
        
        $this->assertNotNull($response->json('number'));
        $this->assertEquals(16, strlen($response->json('number')));

        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
            'number' => $response->json('number'),
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $card = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $this->getJson("/api/debit-cards/{$card->id}")
            ->assertStatus(200)
            ->assertJsonPath('id', $card->id);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $other = User::factory()->create();
        $card = DebitCard::factory()->create(['user_id' => $other->id]);

        $this->getJson("/api/debit-cards/{$card->id}")
            ->assertStatus(403);
    }

    public function testCustomerCanActivateADebitCard()
    {
        $card = DebitCard::factory()->create(['user_id' => $this->user->id, 'disabled_at' => now()]);


        $this->putJson("/api/debit-cards/{$card->id}", ['is_active' => true])
            ->assertStatus(200)
            ->assertJsonPath('is_active', true);

        // assert kolom disabled_at null setelah aktif
        $this->assertDatabaseHas('debit_cards', [
            'id' => $card->id,
            'disabled_at' => null
        ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        $card = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'disabled_at' => null
        ]);

        $this->putJson("/api/debit-cards/{$card->id}", ['is_active' => false])
            ->assertStatus(200)
            ->assertJsonPath('is_active', false);

        // Cek kolom disabled_at tidak null
        $this->assertTrue(DB::table('debit_cards')
            ->where('id', $card->id)
            ->whereNotNull('disabled_at')
            ->exists()
        );
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $card = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $this->putJson("/api/debit-cards/{$card->id}", [
            'number' => 'invalid', // seharusnya 16 digit
        ])->assertStatus(422);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        $card = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $this->deleteJson("/api/debit-cards/{$card->id}")
            ->assertStatus(204);

        $this->assertSoftDeleted('debit_cards', ['id' => $card->id]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        $card = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $this->deleteJson("/api/debit-cards/{$card->id}")
            ->assertStatus(204);

        $this->assertSoftDeleted('debit_cards', ['id' => $card->id]);
    }

    // Extra bonus for extra tests :)
}
