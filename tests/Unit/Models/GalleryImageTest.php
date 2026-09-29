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
        'disk',
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
