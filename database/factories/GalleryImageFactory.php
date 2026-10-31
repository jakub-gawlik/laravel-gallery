<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Jgawlik\LaravelGallery\Models\Gallery;
use Jgawlik\LaravelGallery\Models\GalleryImage;

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
            'thumbnail_path' => null,
            'disk' => 'public',
            'width' => null,
            'height' => null,
            'thumbnail_width' => null,
            'thumbnail_height' => null,
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'alt_text' => $this->faker->sentence(),
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }

    public function withThumbnail(): static
    {
        return $this->state(fn (): array => [
            'thumbnail_path' => 'galleries/thumbs/'.$this->faker->uuid().'.webp',
            'width' => 1600,
            'height' => 900,
            'thumbnail_width' => 800,
            'thumbnail_height' => 450,
        ]);
    }
}
