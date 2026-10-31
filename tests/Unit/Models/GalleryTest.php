<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Jgawlik\LaravelGallery\Models\Gallery;
use Jgawlik\LaravelGallery\Models\GalleryImage;
use Jgawlik\LaravelGallery\Tests\TestCase;

it('has the correct fillable attributes', function () {
    $gallery = new Gallery;

    expect($gallery->getFillable())->toBe([
        'user_id',
        'name',
        'slug',
        'description',
        'image_path',
        'image_thumbnail_path',
        'image_width',
        'image_height',
        'image_thumbnail_width',
        'image_thumbnail_height',
    ]);
});

it('can be created using the factory', function () {
    $gallery = Gallery::factory()->create();

    expect($gallery)->toBeInstanceOf(Gallery::class)
        ->and($gallery->exists)->toBeTrue();
});

it('has many images ordered by sort order', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->create();

    $second = $gallery->images()->create([
        'path' => 'galleries/second.jpg',
        'disk' => 'public',
        'title' => 'Second',
        'sort_order' => 2,
    ]);

    $first = $gallery->images()->create([
        'path' => 'galleries/first.jpg',
        'disk' => 'public',
        'title' => 'First',
        'sort_order' => 1,
    ]);

    $gallery->load('images');

    expect($gallery->images)->toHaveCount(2)
        ->and($gallery->images->first()->id)->toBe($first->id)
        ->and($gallery->images->last()->id)->toBe($second->id);
});

it('deletes related images when deleted', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $gallery = Gallery::factory()->create();

    $path = GalleryImage::factory()->for($gallery)->create()->path;

    $gallery->delete();

    expect(GalleryImage::count())->toBe(0)
        ->and(Storage::disk('public')->exists($path))->toBeFalse();
});

it('scopes galleries to the current user', function () {
    $owned = Gallery::factory()->create(['user_id' => 1]);
    Gallery::factory()->create(['user_id' => 2]);

    expect(Gallery::query()->ownedBy(galleryUser())->pluck('id'))->toContain($owned->id)
        ->and(Gallery::query()->ownedBy(null)->count())->toBe(0);
});

it('keeps route binding neutral so consumers control visibility through policies', function () {
    /** @var TestCase $this */
    $this->actingAs(galleryUser(1));

    $other = Gallery::factory()->create(['user_id' => 2]);

    $resolved = (new Gallery)->resolveRouteBindingQuery(Gallery::query(), $other->id)->first();

    expect($resolved?->getKey())->toBe($other->id);
});

it('knows which user owns it', function () {
    $gallery = Gallery::factory()->create(['user_id' => 1]);

    expect($gallery->isOwnedBy(galleryUser(1)))->toBeTrue()
        ->and($gallery->isOwnedBy(galleryUser(2)))->toBeFalse();
});

it('deletes its cover image and thumbnail when deleted', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $gallery = Gallery::factory()->withImageThumbnail()->create();

    Storage::disk('public')->put((string) $gallery->image_path, 'cover');
    Storage::disk('public')->put((string) $gallery->image_thumbnail_path, 'thumb');

    $gallery->delete();

    Storage::disk('public')->assertMissing((string) $gallery->image_path);
    Storage::disk('public')->assertMissing((string) $gallery->image_thumbnail_path);
});

it('uses the configured disk for its cover image', function () {
    config(['gallery.disk' => 's3']);

    expect(Gallery::factory()->make()->diskName())->toBe('s3');
});

it('exposes the cover image and thumbnail urls', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $gallery = Gallery::factory()->withImageThumbnail()->make([
        'image_path' => 'galleries/cover.jpg',
        'image_thumbnail_path' => 'galleries/thumbs/cover.webp',
    ]);

    expect($gallery->hasImageThumbnail())->toBeTrue()
        ->and($gallery->imageUrl())->toBe(Storage::disk('public')->url('galleries/cover.jpg'))
        ->and($gallery->imageThumbnailUrl())->toBe(Storage::disk('public')->url('galleries/thumbs/cover.webp'))
        ->and($gallery->sourceImagePath())->toBe('galleries/cover.jpg')
        ->and($gallery->currentThumbnailPath())->toBe('galleries/thumbs/cover.webp');
});

it('falls back to the cover image url when there is no thumbnail', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $gallery = Gallery::factory()->withImage()->make(['image_path' => 'galleries/cover.jpg']);

    expect($gallery->hasImageThumbnail())->toBeFalse()
        ->and($gallery->imageThumbnailUrl())->toBe($gallery->imageUrl());
});

it('has no urls without a cover image', function () {
    $gallery = Gallery::factory()->make(['image_path' => null]);

    expect($gallery->imageUrl())->toBeNull()
        ->and($gallery->imageThumbnailUrl())->toBeNull()
        ->and($gallery->sourceImagePath())->toBeNull()
        ->and($gallery->currentThumbnailPath())->toBeNull();
});

it('records a generated cover thumbnail', function () {
    $gallery = Gallery::factory()->withImage()->create();

    $gallery->recordThumbnail('galleries/thumbs/cover.webp', 800, 450);

    $fresh = $gallery->fresh();

    expect($fresh?->image_thumbnail_path)->toBe('galleries/thumbs/cover.webp')
        ->and($fresh?->image_thumbnail_width)->toBe(800)
        ->and($fresh?->image_thumbnail_height)->toBe(450);
});
