<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\DisinfectionSlip;
use App\Models\Truck;
use App\Models\Driver;
use App\Models\Location;
use App\Models\User;
use App\Models\Attachment;

class DisinfectionSlipFactory extends Factory
{
    protected $model = DisinfectionSlip::class;

    public function definition()
    {
        // Generate status: 0=Pending, 1=Disinfecting, 2=In-Transit, 3=Completed
        $status = $this->faker->randomElement([0, 1, 2, 3]);
        
        // Set completed_at only when status is 3 (Completed)
        $completedAt = ($status === 3)
            ? $this->faker->dateTimeBetween('-1 month', 'now')
            : null;

        // Generate optional fields
        $hasAttachment = $this->faker->boolean(60); // 60% chance
        $hasReceivedGuard = $this->faker->boolean(80); // 80% chance
        
        return [
            'truck_id' => Truck::factory(),
            'location_id' => Location::factory(),
            'destination_id' => Location::factory(),
            'driver_id' => Driver::factory(),
            'reason_for_disinfection' => $this->faker->optional(0.7)->sentence(),
            'attachment_ids' => $hasAttachment ? [Attachment::factory()->create()->id] : null,
            'hatchery_guard_id' => User::factory()->guard(),
            'received_guard_id' => $hasReceivedGuard ? User::factory()->guard() : null,
            'status' => $status,
            'completed_at' => $completedAt,
        ];
    }

    /**
     * Indicate that the slip is Pending (status 0).
     */
    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 0,
                'completed_at' => null,
                'received_guard_id' => null,
            ];
        });
    }

    /**
     * Indicate that the slip is disinfecting (status 1).
     */
    public function disinfecting()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 1,
                'completed_at' => null,
            ];
        });
    }

    /**
     * Indicate that the slip is in-transit (status 2).
     */
    public function inTransit()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 2,
                'completed_at' => null,
            ];
        });
    }

    /**
     * Indicate that the slip is completed (status 3).
     */
    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 3,
                'completed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            ];
        });
    }
}