<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DebitCard;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DebitCardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_debit_cards(): void
    {
        $user = User::factory()->create();
        DebitCard::factory()->count(2)->for($user, 'user')->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [['id', 'number', 'bank_name']]
            ]);
    }

    public function test_user_can_create_debit_card(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'number' => '1234-5678-' . fake()->unique()->randomNumber(3),
            'bank_name' => 'BCA'
        ];

        $response = $this->postJson('/api/debit-cards', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.number', $payload['number']);
    }
}
