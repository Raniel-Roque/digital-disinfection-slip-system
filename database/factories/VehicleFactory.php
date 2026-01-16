<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Vehicle;

class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition()
    {
        // Generate realistic plate number format: 3 letters, dash, 4 numbers
        // Example: ABC-1234, XYZ-5678
        $plateNumber = strtoupper($this->faker->bothify('???-####'));
        
        // Ensure uniqueness by checking existing plate numbers
        while (Vehicle::where('plate_number', $plateNumber)->exists()) {
            $plateNumber = strtoupper($this->faker->bothify('???-####'));
        }

        return [
            'plate_number' => $plateNumber,
            'disabled' => false,
        ];
    }

    /**
     * Indicate that the vehicle is disabled.
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
