<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventInvite>
 */
class EventInviteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'invited_email' => fake()->safeEmail(),
            'invited_user_id' => null,
            'token_hash' => hash('sha256', fake()->uuid()),
            'created_by' => User::factory(),
            'status' => 'pending',
            'expires_at' => now()->addDays(3),
            'responded_at' => null,
            'revoked_at' => null,
        ];
    }

    public function forUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'invited_user_id' => $user?->id ?? User::factory(),
            'invited_email' => $user?->email ?? fake()->safeEmail(),
            'token_hash' => null,
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'responded_at' => now(),
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'declined',
            'responded_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expires_at' => now()->subDays(1),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked_at' => now(),
        ]);
    }
}
