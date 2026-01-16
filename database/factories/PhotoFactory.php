<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Photo;
use App\Models\User;

class PhotoFactory extends Factory
{
    protected $model = Photo::class;

    public function definition()
    {
        // Default is uploads (for disinfection slips)
        // Common image extensions for disinfection slip photos
        $extensions = ['jpg', 'jpeg', 'png'];
        $extension = $this->faker->randomElement($extensions);
        
        return [
            'file_path' => 'images/uploads/' . $this->faker->uuid() . '.' . $extension,
            'user_id' => User::factory(),
        ];
    }

    /**
     * State for location logos
     * Location logos typically use PNG format
     */
    public function logo()
    {
        return $this->state(function (array $attributes) {
            return [
                'file_path' => 'images/logos/' . $this->faker->uuid() . '.png',
            ];
        });
    }

    /**
     * State for PDF photos (if needed in future)
     */
    public function pdf()
    {
        return $this->state(function (array $attributes) {
            return [
                'file_path' => 'images/uploads/' . $this->faker->uuid() . '.pdf',
            ];
        });
    }
}
