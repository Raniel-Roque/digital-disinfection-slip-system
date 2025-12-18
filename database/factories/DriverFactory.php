<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Driver;

class DriverFactory extends Factory
{
    protected $model = Driver::class;

    public function definition()
    {
        return [
            'first_name' => $this->faker->firstName(),
            'middle_name' => $this->faker->optional(0.4)->firstName(), // 40% chance of having middle name
            'last_name' => $this->faker->lastName(),
            'disabled' => false, // Default to enabled
        ];
    }

    /**
     * Indicate that the driver is disabled.
     */
    public function disabled()
    {
        return $this->state(function (array $attributes) {
            return [
                'disabled' => true,
            ];
        });
    }
}
