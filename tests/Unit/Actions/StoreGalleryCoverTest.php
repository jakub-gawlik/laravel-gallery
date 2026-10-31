<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Image\ImageException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use Jgawlik\LaravelGallery\Actions\StoreGalleryCover;
use Jgawlik\LaravelGallery\Jobs\GenerateGalleryCoverThumbnail;
use Jgawlik\LaravelGallery\Models\Gallery;
use Jgawlik\LaravelGallery\Tests\TestCase;

beforeEach(function () {
    Storage::fake('public');
});

it('stores the cover, records its dimensions and generates a thumbnail', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->create();

    $result = app(StoreGalleryCover::class)($gallery, UploadedFile::fake()->image('cover.jpg', 1600, 900));

    expect($result)->toBe($gallery)
        ->and($gallery->image_path)->toStartWith('galleries/')
        ->and($gallery->image_width)->toBe(1600)
        ->and($gallery->image_height)->toBe(900)
        ->and($gallery->image_thumbnail_path)->toStartWith('galleries/thumbs/')
        ->and($gallery->image_thumbnail_path)->toEndWith('.webp')
        ->and($gallery->image_thumbnail_width)->toBe(800)
        ->and($gallery->image_thumbnail_height)->toBe(450)
        ->and($gallery->fresh()?->image_thumbnail_path)->toBe($gallery->image_thumbnail_path);

    Storage::disk('public')->assertExists((string) $gallery->image_path);
    Storage::disk('public')->assertExists((string) $gallery->image_thumbnail_path);
});

it('stores the cover in the given sub-directory', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->create();

    app(StoreGalleryCover::class)($gallery, UploadedFile::fake()->image('cover.jpg'), 'covers');

    expect($gallery->image_path)->toStartWith('galleries/covers/')
        ->and($gallery->image_thumbnail_path)->toStartWith('galleries/covers/thumbs/');
});

it('replaces the previous cover and its thumbnail', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->withImageThumbnail()->create([
        'image_path' => 'galleries/old.jpg',
        'image_thumbnail_path' => 'galleries/thumbs/old.webp',
    ]);
    Storage::disk('public')->put('galleries/old.jpg', 'old');
    Storage::disk('public')->put('galleries/thumbs/old.webp', 'old thumb');

    app(StoreGalleryCover::class)($gallery, UploadedFile::fake()->image('cover.jpg', 640, 480));

    expect($gallery->image_path)->not->toBe('galleries/old.jpg')
        ->and($gallery->image_width)->toBe(640)
        ->and($gallery->image_thumbnail_path)->not->toBe('galleries/thumbs/old.webp');

    Storage::disk('public')->assertMissing('galleries/old.jpg');
    Storage::disk('public')->assertMissing('galleries/thumbs/old.webp');
    Storage::disk('public')->assertExists((string) $gallery->image_path);
    Storage::disk('public')->assertExists((string) $gallery->image_thumbnail_path);
});

it('clears the previous thumbnail when thumbnails are disabled', function () {
    /** @var TestCase $this */
    config(['gallery.thumbnails.enabled' => false]);

    $gallery = Gallery::factory()->withImageThumbnail()->create();

    app(StoreGalleryCover::class)($gallery, UploadedFile::fake()->image('cover.jpg', 640, 480));

    expect($gallery->image_width)->toBe(640)
        ->and($gallery->image_height)->toBe(480)
        ->and($gallery->image_thumbnail_path)->toBeNull()
        ->and($gallery->image_thumbnail_width)->toBeNull()
        ->and($gallery->image_thumbnail_height)->toBeNull();

    expect(Storage::disk('public')->allFiles('galleries/thumbs'))->toBeEmpty();
});

it('dispatches a queued job when thumbnails are queued', function () {
    /** @var TestCase $this */
    Bus::fake();
    config(['gallery.thumbnails.queue' => true]);

    $gallery = Gallery::factory()->create();

    app(StoreGalleryCover::class)($gallery, UploadedFile::fake()->image('cover.jpg'));

    expect($gallery->image_thumbnail_path)->toBeNull();

    Bus::assertDispatched(
        GenerateGalleryCoverThumbnail::class,
        fn (GenerateGalleryCoverThumbnail $job): bool => $job->gallery->is($gallery)
    );
});

it('reports but does not fail the upload when the cover cannot be processed', function () {
    /** @var TestCase $this */
    Exceptions::fake();

    $gallery = Gallery::factory()->create();

    app(StoreGalleryCover::class)($gallery, UploadedFile::fake()->createWithContent('cover.jpg', 'not really an image'));

    expect($gallery->image_path)->not->toBeNull()
        ->and($gallery->image_width)->toBeNull()
        ->and($gallery->image_height)->toBeNull()
        ->and($gallery->image_thumbnail_path)->toBeNull();

    Storage::disk('public')->assertExists((string) $gallery->image_path);
    Exceptions::assertReported(ImageException::class);
});

it('throws when the cover cannot be stored', function () {
    /** @var TestCase $this */
    $file = new class(__FILE__, 'cover.jpg') extends UploadedFile
    {
        /**
         * @param  array<string, mixed>  $options
         */
        public function store($path = '', $options = []): string|bool
        {
            return false;
        }
    };

    $gallery = Gallery::factory()->create(['image_path' => null]);

    expect(fn () => app(StoreGalleryCover::class)($gallery, $file))
        ->toThrow(RuntimeException::class, 'Failed to store gallery cover.');

    expect($gallery->fresh()?->image_path)->toBeNull();
});

it('removes the stored file and keeps the previous cover when the gallery cannot be saved', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->create(['image_path' => 'galleries/old.jpg']);
    Storage::disk('public')->put('galleries/old.jpg', 'old');

    Gallery::saving(function (): never {
        throw new RuntimeException('Save failed.');
    });

    expect(fn () => app(StoreGalleryCover::class)($gallery, UploadedFile::fake()->image('cover.jpg')))
        ->toThrow(RuntimeException::class, 'Save failed.');

    expect(Storage::disk('public')->allFiles())->toBe(['galleries/old.jpg'])
        ->and($gallery->fresh()?->image_path)->toBe('galleries/old.jpg');
});
