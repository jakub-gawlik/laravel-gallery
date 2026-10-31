<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Jgawlik\LaravelGallery\Http\Resources\GalleryResource;
use Jgawlik\LaravelGallery\Models\Gallery;
use Jgawlik\LaravelGallery\Models\GalleryImage;
use Jgawlik\LaravelGallery\Tests\TestCase;

it('transforms a gallery without an image', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->create([
        'image_path' => null,
    ]);

    $resource = new GalleryResource($gallery);

    /** @var array<string, mixed> $data */
    $data = json_decode((string) $resource->toResponse(new Request)->getContent(), true)['data'];

    expect($data['id'])->toBe($gallery->id)
        ->and($data['name'])->toBe($gallery->name)
        ->and($data['slug'])->toBe($gallery->slug)
        ->and($data['description'])->toBe($gallery->description)
        ->and($data['image_url'])->toBeNull()
        ->and($data['image_width'])->toBeNull()
        ->and($data['image_height'])->toBeNull()
        ->and($data['image_thumbnail_url'])->toBeNull()
        ->and($data['image_thumbnail_width'])->toBeNull()
        ->and($data['image_thumbnail_height'])->toBeNull()
        ->and(array_key_exists('images', $data))->toBeFalse();
});

it('transforms a gallery with an image url', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $gallery = Gallery::factory()->create([
        'image_path' => 'galleries/cover.jpg',
        'image_width' => 1600,
        'image_height' => 900,
    ]);

    /** @var array<string, mixed> $resource */
    $resource = (new GalleryResource($gallery))->toArray(new Request);

    expect($resource['image_url'])->toBe(Storage::disk('public')->url('galleries/cover.jpg'))
        ->and($resource['image_width'])->toBe(1600)
        ->and($resource['image_height'])->toBe(900)
        ->and($resource['image_thumbnail_url'])->toBe($resource['image_url'])
        ->and($resource['image_thumbnail_width'])->toBe(1600)
        ->and($resource['image_thumbnail_height'])->toBe(900);
});

it('transforms a gallery with a cover thumbnail', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $gallery = Gallery::factory()->withImageThumbnail()->create([
        'image_path' => 'galleries/cover.jpg',
        'image_thumbnail_path' => 'galleries/thumbs/cover.webp',
    ]);

    /** @var array<string, mixed> $resource */
    $resource = (new GalleryResource($gallery))->toArray(new Request);

    expect($resource['image_url'])->toBe(Storage::disk('public')->url('galleries/cover.jpg'))
        ->and($resource['image_width'])->toBe(1600)
        ->and($resource['image_height'])->toBe(900)
        ->and($resource['image_thumbnail_url'])->toBe(Storage::disk('public')->url('galleries/thumbs/cover.webp'))
        ->and($resource['image_thumbnail_width'])->toBe(800)
        ->and($resource['image_thumbnail_height'])->toBe(450);
});

it('includes images when loaded', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $gallery = Gallery::factory()->create();

    GalleryImage::factory()->for($gallery)->count(2)->create();

    /** @var array<string, mixed> $resource */
    $resource = (new GalleryResource($gallery->load('images')))->toArray(new Request);

    expect($resource['images'])->toHaveCount(2);
});
