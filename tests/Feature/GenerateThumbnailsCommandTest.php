<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Jgawlik\LaravelGallery\Jobs\GenerateGalleryCoverThumbnail;
use Jgawlik\LaravelGallery\Jobs\GenerateGalleryThumbnail;
use Jgawlik\LaravelGallery\Models\Gallery;
use Jgawlik\LaravelGallery\Models\GalleryImage;
use Jgawlik\LaravelGallery\Tests\TestCase;

beforeEach(function () {
    Storage::fake('public');
});

function storedImageWithoutThumbnail(string $path): GalleryImage
{
    Storage::disk('public')->put($path, UploadedFile::fake()->image(basename($path), 800, 600)->getContent());

    return GalleryImage::factory()->create(['path' => $path]);
}

function galleryWithCoverWithoutThumbnail(string $path): Gallery
{
    Storage::disk('public')->put($path, UploadedFile::fake()->image(basename($path), 800, 600)->getContent());

    return Gallery::factory()->create(['image_path' => $path]);
}

it('generates thumbnails for images and covers that do not have one', function () {
    /** @var TestCase $this */
    $first = storedImageWithoutThumbnail('galleries/one.jpg');
    $second = storedImageWithoutThumbnail('galleries/two.jpg');
    $existing = GalleryImage::factory()->withThumbnail()->create();
    $cover = galleryWithCoverWithoutThumbnail('galleries/cover.jpg');
    $existingCover = Gallery::factory()->withImageThumbnail()->create();
    Gallery::factory()->create(['image_path' => null]);

    expect(Artisan::call('gallery:thumbnails'))->toBe(0)
        ->and(Artisan::output())->toContain('Generated 3 thumbnail(s), 0 failed.');

    expect($first->fresh()?->thumbnail_path)->not->toBeNull()
        ->and($second->fresh()?->thumbnail_path)->not->toBeNull()
        ->and($existing->fresh()?->thumbnail_path)->toBe($existing->thumbnail_path)
        ->and($cover->fresh()?->image_thumbnail_path)->not->toBeNull()
        ->and($existingCover->fresh()?->image_thumbnail_path)->toBe($existingCover->image_thumbnail_path);
});

it('regenerates existing thumbnails with the force option', function () {
    /** @var TestCase $this */
    $image = storedImageWithoutThumbnail('galleries/one.jpg');
    $image->forceFill(['thumbnail_path' => 'galleries/thumbs/old.webp'])->save();
    Storage::disk('public')->put('galleries/thumbs/old.webp', 'old');

    $gallery = galleryWithCoverWithoutThumbnail('galleries/cover.jpg');
    $gallery->forceFill(['image_thumbnail_path' => 'galleries/thumbs/old-cover.webp'])->save();
    Storage::disk('public')->put('galleries/thumbs/old-cover.webp', 'old');

    expect(Artisan::call('gallery:thumbnails', ['--force' => true]))->toBe(0)
        ->and(Artisan::output())->toContain('Generated 2 thumbnail(s), 0 failed.');

    expect($image->fresh()?->thumbnail_path)->not->toBe('galleries/thumbs/old.webp')
        ->and($gallery->fresh()?->image_thumbnail_path)->not->toBe('galleries/thumbs/old-cover.webp');
    Storage::disk('public')->assertMissing('galleries/thumbs/old.webp');
    Storage::disk('public')->assertMissing('galleries/thumbs/old-cover.webp');
});

it('dispatches queued jobs with the queue option', function () {
    /** @var TestCase $this */
    Queue::fake();

    storedImageWithoutThumbnail('galleries/one.jpg');
    storedImageWithoutThumbnail('galleries/two.jpg');
    galleryWithCoverWithoutThumbnail('galleries/cover.jpg');

    expect(Artisan::call('gallery:thumbnails', ['--queue' => true]))->toBe(0)
        ->and(Artisan::output())->toContain('Dispatched 3 thumbnail job(s).');

    Queue::assertPushed(GenerateGalleryThumbnail::class, 2);
    Queue::assertPushed(GenerateGalleryCoverThumbnail::class, 1);
    expect(GalleryImage::whereNull('thumbnail_path')->count())->toBe(2)
        ->and(Gallery::whereNotNull('image_path')->whereNull('image_thumbnail_path')->count())->toBe(1);
});

it('reports when there is nothing to do', function () {
    /** @var TestCase $this */
    GalleryImage::factory()->withThumbnail()->create();
    Gallery::factory()->withImageThumbnail()->create();

    expect(Artisan::call('gallery:thumbnails'))->toBe(0)
        ->and(Artisan::output())->toContain('All gallery images and covers already have thumbnails.');
});

it('continues past failures and exits with an error code', function () {
    /** @var TestCase $this */
    $broken = GalleryImage::factory()->create(['path' => 'galleries/missing.jpg']);
    $fine = storedImageWithoutThumbnail('galleries/fine.jpg');
    $brokenCover = Gallery::factory()->create(['image_path' => 'galleries/missing-cover.jpg']);

    $exitCode = Artisan::call('gallery:thumbnails');
    $output = Artisan::output();

    expect($exitCode)->toBe(1)
        ->and($output)->toContain("Image #{$broken->id} (galleries/missing.jpg)")
        ->and($output)->toContain("Gallery #{$brokenCover->id} cover (galleries/missing-cover.jpg)")
        ->and($output)->toContain('Generated 1 thumbnail(s), 2 failed.');

    expect($broken->fresh()?->thumbnail_path)->toBeNull()
        ->and($brokenCover->fresh()?->image_thumbnail_path)->toBeNull()
        ->and($fine->fresh()?->thumbnail_path)->not->toBeNull();
});
