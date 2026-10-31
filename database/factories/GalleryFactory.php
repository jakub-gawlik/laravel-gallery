<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Jgawlik\LaravelGallery\Models\Gallery;

/**
 * @extends Factory<Gallery>
 */
class GalleryFactory extends Factory
{
    protected $model = Gallery::class;

    public function definition(): array
    {
        $name = (string) $this->faker->words(3, true);

        return [
            'user_id' => 1,
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(1000, 9999),
            'description' => $this->faker->sentence(),
        ];
    }

    public function withImage(): static
    {
        return $this->state(fn (): array => [
            'image_path' => 'galleries/'.$this->faker->uuid().'.jpg',
            'image_width' => 1600,
            'image_height' => 900,
        ]);
    }

    public function withImageThumbnail(): static
    {
        return $this->withImage()->state(fn (): array => [
            'image_thumbnail_path' => 'galleries/thumbs/'.$this->faker->uuid().'.webp',
            'image_thumbnail_width' => 800,
            'image_thumbnail_height' => 450,
        ]);
    }
}
