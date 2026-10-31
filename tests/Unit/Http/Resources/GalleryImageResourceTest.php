<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Jgawlik\LaravelGallery\Http\Resources\GalleryImageResource;
use Jgawlik\LaravelGallery\Models\Gallery;
use Jgawlik\LaravelGallery\Models\GalleryImage;
use Jgawlik\LaravelGallery\Tests\TestCase;

it('transforms a gallery image', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $gallery = Gallery::factory()->create();

    $image = GalleryImage::factory()->for($gallery)->create([
        'path' => 'galleries/photo.jpg',
        'disk' => 'public',
        'title' => 'A great photo',
        'description' => 'This is a great photo',
        'alt_text' => 'A photo',
        'sort_order' => 5,
        'width' => 1600,
        'height' => 900,
        'thumbnail_path' => 'galleries/thumbs/photo.webp',
        'thumbnail_width' => 800,
        'thumbnail_height' => 450,
    ]);

    /** @var array<string, mixed> $resource */
    $resource = (new GalleryImageResource($image))->toArray(new Request);

    expect($resource['id'])->toBe($image->id)
        ->and($resource['path'])->toBe('galleries/photo.jpg')
        ->and($resource['url'])->toBe(Storage::disk('public')->url('galleries/photo.jpg'))
        ->and($resource['width'])->toBe(1600)
        ->and($resource['height'])->toBe(900)
        ->and($resource['thumbnail_url'])->toBe(Storage::disk('public')->url('galleries/thumbs/photo.webp'))
        ->and($resource['thumbnail_width'])->toBe(800)
        ->and($resource['thumbnail_height'])->toBe(450)
        ->and($resource['title'])->toBe('A great photo')
        ->and($resource['description'])->toBe('This is a great photo')
        ->and($resource['alt_text'])->toBe('A photo')
        ->and($resource['sort_order'])->toBe(5);
});

it('falls back to the original image when there is no thumbnail', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $image = GalleryImage::factory()->for(Gallery::factory())->create([
        'path' => 'galleries/photo.jpg',
        'thumbnail_path' => null,
        'width' => 1200,
        'height' => 800,
        'thumbnail_width' => null,
        'thumbnail_height' => null,
    ]);

    /** @var array<string, mixed> $resource */
    $resource = (new GalleryImageResource($image))->toArray(new Request);

    expect($resource['thumbnail_url'])->toBe($resource['url'])
        ->and($resource['thumbnail_width'])->toBe(1200)
        ->and($resource['thumbnail_height'])->toBe(800);
});
