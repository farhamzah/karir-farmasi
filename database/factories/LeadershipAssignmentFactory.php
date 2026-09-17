<?php

namespace Database\Factories;

use App\Models\LeadershipAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadershipAssignment>
 */
class LeadershipAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_core_user_id' => fake()->uuid(),
            'scope_type' => 'program',
            'scope_reference' => 'S1 Farmasi',
            'scope_label' => 'S1 Farmasi',
            'role_label' => 'kaprodi',
            'active' => true,
        ];
    }
}
