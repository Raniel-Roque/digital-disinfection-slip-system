<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create default user
        User::factory()->create([
            'first_name' => 'John',
            'middle_name' => 'Corpuz',
            'last_name' => 'Doe',
            'username' => 'JDoe',
            'user_type' => '2',
        ]);

        // Create default settings
        $defaultSettings = [
            [
                'setting_name' => 'attachment_retention_days',
                'value' => '30',
            ],
            [
                'setting_name' => 'default_guard_password',
                'value' => 'brookside25',
            ],
            [
                'setting_name' => 'default_location_logo',
                'value' => 'images/logo/BGC.png',
            ],
            [
                'setting_name' => 'log_retention_months',
                'value' => '6',
            ],
            [
                'setting_name' => 'resolved_reports_retention_months',
                'value' => '6',
            ],
        ];

        foreach ($defaultSettings as $setting) {
            Setting::factory()->create($setting);
        }
    }
}
