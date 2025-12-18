<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Attachment;

class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    public function definition()
    {
        // Default is uploads (for disinfection slips)
        // Common image extensions for disinfection slip photos
        $extensions = ['jpg', 'jpeg', 'png'];
        $extension = $this->faker->randomElement($extensions);
        
        return [
            'file_path' => 'images/uploads/' . $this->faker->uuid() . '.' . $extension,
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
     * State for PDF attachments (if needed in future)
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
