<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Couple;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_profile_success(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'born_date' => '1995-01-01',
            'gender' => 'male',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/user/update', [
            'name' => 'New Name',
            'born_date' => '2000-12-31',
            'gender' => 'female',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.name', 'New Name')
            ->assertJsonPath('user.born_date', '2000-12-31')
            ->assertJsonPath('user.gender', 'female');

        $user->refresh();
        $this->assertEquals('New Name', $user->name);
        $this->assertEquals('2000-12-31', $user->born_date);
        $this->assertEquals('female', $user->gender);
    }

    public function test_update_profile_validation_error(): void
    {
        $user = User::factory()->create();

        // Invalid gender and invalid date format
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/user/update', [
            'gender' => 'other',
            'born_date' => 'not-a-date',
        ]);

        $response->assertStatus(422);
    }

    public function test_update_anniversary_success(): void
    {
        $couple = Couple::create([
            'is_active' => true,
            'anniversary_start_date' => '2020-01-01 00:00:00'
        ]);
        $user = User::factory()->create(['couple_id' => $couple->id]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/couple/anniversary', [
            'anniversary_date' => '2025-05-17 12:00:00',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('anniversary_date', '2025-05-17 12:00:00');

        $couple->refresh();
        $this->assertEquals('2025-05-17 12:00:00', $couple->anniversary_start_date);
    }
}
