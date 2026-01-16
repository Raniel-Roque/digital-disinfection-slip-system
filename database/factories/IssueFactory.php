<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\User;
use App\Models\DisinfectionSlip;
use Illuminate\Database\Eloquent\Factories\Factory;

class IssueFactory extends Factory
{
    protected $model = Issue::class;

    public function definition()
    {
        // 70% chance of having a slip_id, 30% miscellaneous
        $hasSlip = $this->faker->boolean(70);
        $isResolved = $this->faker->boolean(30);
        $resolvedAt = $isResolved ? $this->faker->dateTimeBetween('-6 months', 'now') : null;
        
        // Get admin/superadmin IDs for resolved_by (only if resolved)
        // Try to use existing admin/superadmin, otherwise leave null
        $resolvedBy = null;
        if ($isResolved) {
            $adminsAndSuperAdmins = User::whereIn('user_type', [1, 2])->pluck('id')->toArray();
            if (!empty($adminsAndSuperAdmins)) {
                $resolvedBy = $this->faker->randomElement($adminsAndSuperAdmins);
            }
            // If no admins exist, leave resolved_by as null (can be set manually if needed)
        }
        
        return [
            'user_id' => User::factory(),
            'slip_id' => $hasSlip ? DisinfectionSlip::factory() : null,
            'description' => $this->faker->paragraph(),
            'resolved_at' => $resolvedAt,
            'resolved_by' => $resolvedBy,
        ];
    }
}
