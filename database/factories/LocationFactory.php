<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Location;
use App\Models\Photo;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition()
    {
        // Generate realistic location names (hatcheries, farms, facilities)
        $locationTypes = ['Hatchery', 'Farm', 'Facility', 'Processing Plant', 'Distribution Center'];
        $locationNames = [
            'BGC', 'Baliwag', 'San Rafael', 'Angeles', 'Tarlac',
            'Pampanga', 'Bulacan', 'Manila', 'Laguna', 'Cavite',
            'Batangas', 'Quezon', 'Nueva Ecija', 'Zambales'
        ];
        
        $name = $this->faker->randomElement($locationNames) . ' ' . $this->faker->randomElement($locationTypes);

        return [
            'location_name' => $name,
            'photo_id' => $this->faker->optional(0.7)->passthrough(Photo::factory()->logo()),
            'disabled' => false,
            'create_slip' => $this->faker->boolean(80), // 80% can create slips
        ];
    }

    /**
     * Indicate that the location is disabled.
     */
    public function disabled()
    {
        return $this->state(function (array $attributes) {
            return [
                'disabled' => true,
            ];
        });
    }

    /**
     * Indicate that the location has a logo.
     */
    public function withLogo()
    {
        return $this->state(function (array $attributes) {
            return [
                'photo_id' => Photo::factory()->logo(),
            ];
        });
    }
}
