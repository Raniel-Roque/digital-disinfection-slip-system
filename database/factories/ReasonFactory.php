<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Reason;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reason>
 */
class ReasonFactory extends Factory
{
    protected $model = Reason::class;

    public function definition()
    {
        $reasons = [
            'Pick-up Eggs',
            'Pick-up Cull',
            'Deliver Feeds',
            'Hauling of Manure',
            'Back to Feed Mill',
            'Pick-up Cash (BRINKS Armor Car)',
            'Deliver Supplies',
            'Maintenance Visit',
            'Quality Control Check',
            'Emergency Transport',
        ];

        return [
            'reason_text' => $this->faker->randomElement($reasons),
            'is_disabled' => $this->faker->boolean(10), // 10% chance of being disabled
        ];
    }

    /**
     * Indicate that the reason is disabled.
     */
    public function disabled()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_disabled' => true,
            ];
        });
    }

    /**
     * Indicate that the reason is enabled.
     */
    public function enabled()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_disabled' => false,
            ];
        });
    }
}