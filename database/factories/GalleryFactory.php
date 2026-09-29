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
}
