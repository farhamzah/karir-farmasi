<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyUser>
 */
class CompanyUserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->companyEmail(),
            'password' => 'CompanyPass123',
            'role' => 'recruiter',
            'active' => true,
        ];
    }

    public function administrator(): static
    {
        return $this->state(fn () => ['role' => 'company_admin']);
    }
}
