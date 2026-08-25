<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Database\Factories;

use Jgawlik\LaravelGallery\Models\Gallery;
use Jgawlik\LaravelGallery\Models\GalleryImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleryImage>
 */
class GalleryImageFactory extends Factory
{
    protected $model = GalleryImage::class;

    public function definition(): array
    {
        return [
            'gallery_id' => Gallery::factory(),
            'path' => 'galleries/'.$this->faker->uuid().'.jpg',
            'disk' => 'public',
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'alt_text' => $this->faker->sentence(),
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }
}
