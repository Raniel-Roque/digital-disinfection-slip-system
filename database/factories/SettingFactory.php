<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Setting;

class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition()
    {
        // Realistic settings that exist in the system
        $realisticSettings = [
            'attachment_retention_days' => ['7', '14', '30', '60', '90'],
            'default_guard_password' => ['brookside25'],
            'default_location_logo' => ['images/logo/BGC.png'],
            'log_retention_months' => ['3', '6', '12', '24'],
        ];

        $settingName = $this->faker->randomElement(array_keys($realisticSettings));
        $value = $this->faker->randomElement($realisticSettings[$settingName]);

        return [
            'setting_name' => $settingName,
            'value' => $value,
        ];
    }

    /**
     * Create attachment retention setting
     */
    public function attachmentRetention()
    {
        return $this->state(function (array $attributes) {
            return [
                'setting_name' => 'attachment_retention_days',
                'value' => (string) $this->faker->numberBetween(7, 90),
            ];
        });
    }

    /**
     * Create default guard password setting
     */
    public function defaultGuardPassword()
    {
        return $this->state(function (array $attributes) {
            return [
                'setting_name' => 'default_guard_password',
                'value' => 'brookside25',
            ];
        });
    }

    /**
     * Create default location logo setting
     */
    public function defaultLocationLogo()
    {
        return $this->state(function (array $attributes) {
            return [
                'setting_name' => 'default_location_logo',
                'value' => 'images/logo/BGC.png',
            ];
        });
    }

    /**
     * Create log retention setting
     */
    public function logRetention()
    {
        return $this->state(function (array $attributes) {
            return [
                'setting_name' => 'log_retention_months',
                'value' => (string) $this->faker->numberBetween(3, 24),
            ];
        });
    }
}
