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
        ->and(array_key_exists('images', $data))->toBeFalse();
});

it('transforms a gallery with an image url', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $gallery = Gallery::factory()->create([
        'image_path' => 'galleries/cover.jpg',
    ]);

    /** @var array<string, mixed> $resource */
    $resource = (new GalleryResource($gallery))->toArray(new Request);

    expect($resource['image_url'])
        ->toBe(Storage::disk('public')->url('galleries/cover.jpg'));
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
