<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Jgawlik\LaravelGallery\Models\Gallery;
use Jgawlik\LaravelGallery\Models\GalleryImage;
use Jgawlik\LaravelGallery\Tests\TestCase;

it('has the correct fillable attributes', function () {
    $image = new GalleryImage;

    expect($image->getFillable())->toBe([
        'gallery_id',
        'path',
        'thumbnail_path',
        'disk',
        'width',
        'height',
        'thumbnail_width',
        'thumbnail_height',
        'title',
        'description',
        'alt_text',
        'sort_order',
    ]);
});

it('can be created using the factory', function () {
    /** @var TestCase $this */
    $image = GalleryImage::factory()->create();

    expect($image)->toBeInstanceOf(GalleryImage::class)
        ->and($image->exists)->toBeTrue()
        ->and($image->gallery)->toBeInstanceOf(Gallery::class);
});

it('belongs to a gallery', function () {
    /** @var TestCase $this */
    $image = GalleryImage::factory()->create();

    expect($image->gallery)->toBeInstanceOf(Gallery::class)
        ->and($image->gallery->id)->toBe($image->gallery_id);
});

it('deletes the file from disk when deleted', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $gallery = Gallery::factory()->create();

    $image = GalleryImage::factory()->for($gallery)->create();

    Storage::disk('public')->put($image->path, 'content');

    $image->delete();

    Storage::disk('public')->assertMissing($image->path);
});

it('falls back to the public disk when disk is null', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $gallery = Gallery::factory()->create();

    $image = GalleryImage::factory()->for($gallery)->create([
        'disk' => null,
    ]);

    Storage::disk('public')->put($image->path, 'content');

    $image->delete();

    Storage::disk('public')->assertMissing($image->path);
});

it('scopes images to the current user gallery', function () {
    $ownedGallery = Gallery::factory()->create(['user_id' => 1]);
    $otherGallery = Gallery::factory()->create(['user_id' => 2]);

    $owned = GalleryImage::factory()->for($ownedGallery)->create();
    GalleryImage::factory()->for($otherGallery)->create();

    expect(GalleryImage::query()->ownedBy(galleryUser())->pluck('id'))->toContain($owned->id)
        ->and(GalleryImage::query()->ownedBy(null)->count())->toBe(0);
});

it('keeps image route binding neutral so consumers control visibility through policies', function () {
    /** @var TestCase $this */
    $this->actingAs(galleryUser(1));

    $gallery = Gallery::factory()->create(['user_id' => 2]);
    $image = GalleryImage::factory()->for($gallery)->create();

    $resolved = (new GalleryImage)->resolveRouteBindingQuery(GalleryImage::query(), $image->id)->first();

    expect($resolved?->getKey())->toBe($image->id);
});

it('delegates ownership to its gallery', function () {
    $gallery = Gallery::factory()->create(['user_id' => 1]);
    $image = GalleryImage::factory()->for($gallery)->create();

    expect($image->isOwnedBy(galleryUser(1)))->toBeTrue()
        ->and($image->isOwnedBy(galleryUser(2)))->toBeFalse();
});

it('deletes the thumbnail from disk when deleted', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $image = GalleryImage::factory()->withThumbnail()->create();

    Storage::disk('public')->put($image->path, 'content');
    Storage::disk('public')->put((string) $image->thumbnail_path, 'thumb');

    $image->delete();

    Storage::disk('public')->assertMissing($image->path);
    Storage::disk('public')->assertMissing((string) $image->thumbnail_path);
});

it('resolves the disk name with a public fallback', function () {
    expect(GalleryImage::factory()->make(['disk' => 's3'])->diskName())->toBe('s3')
        ->and(GalleryImage::factory()->make(['disk' => null])->diskName())->toBe('public');
});

it('exposes the original and thumbnail urls', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $image = GalleryImage::factory()->withThumbnail()->make([
        'path' => 'galleries/photo.jpg',
        'thumbnail_path' => 'galleries/thumbs/photo.webp',
    ]);

    expect($image->hasThumbnail())->toBeTrue()
        ->and($image->url())->toBe(Storage::disk('public')->url('galleries/photo.jpg'))
        ->and($image->thumbnailUrl())->toBe(Storage::disk('public')->url('galleries/thumbs/photo.webp'));
});

it('falls back to the original url when there is no thumbnail', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $image = GalleryImage::factory()->make([
        'path' => 'galleries/photo.jpg',
        'thumbnail_path' => null,
    ]);

    expect($image->hasThumbnail())->toBeFalse()
        ->and($image->thumbnailUrl())->toBe($image->url());
});
