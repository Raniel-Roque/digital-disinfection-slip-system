<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Setting;
use App\Models\Reason;
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
        // Create default users
        User::factory()->create([
            'first_name' => 'Jeff',
            'middle_name' => null,
            'last_name' => 'Montiano',
            'username' => 'JMontiano',
            'user_type' => '2',
        ]);

        User::factory()->create([
            'first_name' => 'Adam',
            'middle_name' => null,
            'last_name' => 'Trinidad',
            'username' => 'ATrinidad',
            'user_type' => '2',
        ]);

        User::factory()->create([
            'first_name' => 'Iverson',
            'middle_name' => null,
            'last_name' => 'Guno',
            'username' => 'IGuno',
            'user_type' => '2',
        ]);

        User::factory()->create([
            'first_name' => 'Raniel',
            'middle_name' => null,
            'last_name' => 'Roque',
            'username' => 'RRoque',
            'user_type' => '2',
        ]);
        
        User::factory()->create([
            'first_name' => 'Jenny',
            'middle_name' => null,
            'last_name' => 'Santos',
            'username' => 'JSantos',
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
                'value' => '3',
            ],
            [
                'setting_name' => 'resolved_reports_retention_months',
                'value' => '3',
            ],
            [
                'setting_name' => 'soft_deleted_retention_months',
                'value' => '3',
            ],
        ];

        foreach ($defaultSettings as $setting) {
            Setting::factory()->create($setting);
        }

        // Create default reasons
        $defaultReasons = [
            'Pick-up Eggs',
            'Pick-up Cull',
            'Deliver Feeds',
            'Hauling of Manure',
            'Back to Feed Mill',
            'Pick-up Cash (BRINKS Armor Car)',
        ];

        foreach ($defaultReasons as $reason) {
            Reason::create([
                'reason_text' => $reason,
                'is_disabled' => false,
            ]);
        }
    }
}