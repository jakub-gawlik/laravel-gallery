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
