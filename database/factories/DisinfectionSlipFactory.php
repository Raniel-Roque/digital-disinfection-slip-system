<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
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
        return [
            'truck_id' => Truck::factory(),
            'location_id' => Location::factory(),
            'destination_id' => Location::factory(),
            'driver_id' => Driver::factory(),
            'reason_for_disinfection' => $this->faker->optional()->paragraph(),
            'attachment_id' => Attachment::factory(),
            'hatchery_guard_id' => User::factory()->state(['user_type' => 0]),
            'received_guard_id' => User::factory()->state(['user_type' => 0]),
            'status' => $this->faker->numberBetween(0, 2),
        ];
    }
}