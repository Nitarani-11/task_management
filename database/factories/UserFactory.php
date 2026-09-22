<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'user',
            'department' => fake()->randomElement(['Finance', 'HR', 'IT', 'Operation']),
            'years_of_experience' => fake()->numberBetween(1, 15),
            'location' => fake()->randomElement(['Bhubaneswar', 'Delhi', 'Mumbai', 'Bengaluru', 'Hyderabad']),
            'active_tasks_count' => 0,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    public function manager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'manager',
        ]);
    }

    public function finance(int $experience = 5): static
    {
        return $this->state(fn (array $attributes) => [
            'department' => 'Finance',
            'years_of_experience' => $experience,
        ]);
    }

    public function hr(int $experience = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'department' => 'HR',
            'years_of_experience' => $experience,
        ]);
    }

    public function it(int $experience = 4): static
    {
        return $this->state(fn (array $attributes) => [
            'department' => 'IT',
            'years_of_experience' => $experience,
        ]);
    }

    public function operation(int $experience = 2): static
    {
        return $this->state(fn (array $attributes) => [
            'department' => 'Operation',
            'years_of_experience' => $experience,
        ]);
    }
}
