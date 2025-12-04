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
            'user_type' => '1',
        ]);

        // Create default setting for attachment retention
        Setting::factory()->create([
            'setting_name' => 'attachment_retention_days',
            'value' => '30',
        ]);
    }
}
