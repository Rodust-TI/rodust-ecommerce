<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'phone' => fake()->phoneNumber(),
            'cpf' => fake()->numerify('###########'),
            'person_type' => 'F',
            'email_verified_at' => now(),
        ];
    }

    /**
     * Indicate that the customer's email should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the customer is a company (CNPJ).
     */
    public function company(): static
    {
        return $this->state(fn (array $attributes) => [
            'person_type' => 'J',
            'cnpj' => fake()->numerify('##############'),
            'fantasy_name' => fake()->company(),
            'state_registration' => fake()->numerify('########'),
            'state_uf' => fake()->stateAbbr(),
        ]);
    }
}
