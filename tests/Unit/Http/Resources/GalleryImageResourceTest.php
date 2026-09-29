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
    ]);

    /** @var array<string, mixed> $resource */
    $resource = (new GalleryImageResource($image))->toArray(new Request);

    expect($resource['id'])->toBe($image->id)
        ->and($resource['path'])->toBe('galleries/photo.jpg')
        ->and($resource['url'])->toBe(Storage::disk('public')->url('galleries/photo.jpg'))
        ->and($resource['title'])->toBe('A great photo')
        ->and($resource['description'])->toBe('This is a great photo')
        ->and($resource['alt_text'])->toBe('A photo')
        ->and($resource['sort_order'])->toBe(5);
});
