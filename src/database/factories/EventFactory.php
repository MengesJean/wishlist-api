<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'start_at' => fake()->dateTimeBetween('now', '+1 month'),
            'created_by' => User::factory(),
            'invite_token_hash' => null,
            'invite_token_created_at' => null,
        ];
    }

    public function withInviteToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'invite_token_hash' => hash('sha256', fake()->uuid()),
            'invite_token_created_at' => now(),
        ]);
    }
}
