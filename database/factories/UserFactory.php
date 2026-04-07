<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fname' => fake()->firstName(),
            'lname' => fake()->lastName(),
            'agency' => 'LGU',
            'position' => 'Planning Officer',
            'region' => 'CAR',
            'province' => 'Benguet',
            'office' => fake()->city(),
            'emailaddress' => fake()->unique()->safeEmail(),
            'mobileno' => '09' . fake()->numerify('#########'),
            'username' => fake()->unique()->userName(),
            'role' => \App\Models\User::ROLE_LGU,
            'status' => 'active',
            'access' => '',
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
