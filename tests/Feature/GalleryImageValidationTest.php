<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Jgawlik\LaravelGallery\Models\Gallery;
use Jgawlik\LaravelGallery\Models\GalleryImage;
use Jgawlik\LaravelGallery\Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->actingAs(galleryUser());
});

it('requires images to upload', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->create();

    $this->postJson("/api/v1/galleries/{$gallery->id}/images", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['images']);
});

it('rejects non image files', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $gallery = Gallery::factory()->create();

    $this->postJson("/api/v1/galleries/{$gallery->id}/images", [
        'images' => [
            [
                'file' => UploadedFile::fake()->create('document.pdf', 100),
                'title' => 'A document',
            ],
        ],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['images.0.file']);
});

it('accepts images without a title or description', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $gallery = Gallery::factory()->create();

    $this->postJson("/api/v1/galleries/{$gallery->id}/images", [
        'images' => [
            [
                'file' => UploadedFile::fake()->image('one.jpg'),
            ],
        ],
    ])
        ->assertCreated();

    $image = $gallery->fresh()->images->first();

    expect($image->title)->toBeNull()
        ->and($image->description)->toBeNull();
});

it('accepts an optional title and description for each image', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $gallery = Gallery::factory()->create();

    $this->postJson("/api/v1/galleries/{$gallery->id}/images", [
        'images' => [
            [
                'file' => UploadedFile::fake()->image('one.jpg'),
                'title' => 'A photo',
                'description' => 'A nice photo',
            ],
        ],
    ])
        ->assertCreated()
        ->assertJsonPath('data.0.title', 'A photo')
        ->assertJsonPath('data.0.description', 'A nice photo');
});

it('returns a 404 when deleting an image from the wrong gallery', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->create();
    $otherGallery = Gallery::factory()->create();

    $image = GalleryImage::factory()->for($otherGallery)->create();

    $this->deleteJson("/api/v1/galleries/{$gallery->id}/images/{$image->id}")
        ->assertNotFound();
});

it('orders uploaded images by sort order', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $gallery = Gallery::factory()->create();

    GalleryImage::factory()->for($gallery)->create(['sort_order' => 10]);

    $this->postJson("/api/v1/galleries/{$gallery->id}/images", [
        'images' => [
            [
                'file' => UploadedFile::fake()->image('one.jpg'),
                'title' => 'One',
            ],
            [
                'file' => UploadedFile::fake()->image('two.jpg'),
                'title' => 'Two',
            ],
        ],
    ])
        ->assertCreated();

    $orders = $gallery->fresh()->images->pluck('sort_order')->toArray();

    expect($orders)->toBe([10, 11, 12]);
});
