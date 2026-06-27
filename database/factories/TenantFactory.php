<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'subscription_plan' => $this->faker->randomElement(['basic', 'premium', 'enterprise']),
        ];
    }
}
