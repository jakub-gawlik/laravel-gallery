<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Image\ImageException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use Jgawlik\LaravelGallery\Actions\StoreGalleryImage;
use Jgawlik\LaravelGallery\Jobs\GenerateGalleryThumbnail;
use Jgawlik\LaravelGallery\Models\Gallery;
use Jgawlik\LaravelGallery\Models\GalleryImage;
use Jgawlik\LaravelGallery\Tests\TestCase;

beforeEach(function () {
    Storage::fake('public');
});

it('stores the original, records its dimensions and generates a thumbnail', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->create();

    $image = app(StoreGalleryImage::class)(
        $gallery,
        UploadedFile::fake()->image('photo.jpg', 1600, 900),
        ['title' => 'Sunset', 'description' => 'Over the sea', 'alt_text' => 'A sunset', 'sort_order' => 3],
    );

    expect($image)->toBeInstanceOf(GalleryImage::class)
        ->and($image->gallery_id)->toBe($gallery->id)
        ->and($image->disk)->toBe('public')
        ->and($image->path)->toStartWith('galleries/')
        ->and($image->width)->toBe(1600)
        ->and($image->height)->toBe(900)
        ->and($image->title)->toBe('Sunset')
        ->and($image->description)->toBe('Over the sea')
        ->and($image->alt_text)->toBe('A sunset')
        ->and($image->sort_order)->toBe(3)
        ->and($image->thumbnail_path)->toStartWith('galleries/thumbs/')
        ->and($image->thumbnail_path)->toEndWith('.webp')
        ->and($image->thumbnail_width)->toBe(800)
        ->and($image->thumbnail_height)->toBe(450);

    Storage::disk('public')->assertExists($image->path);
    Storage::disk('public')->assertExists((string) $image->thumbnail_path);
});

it('appends to the end of the gallery when no sort order is given', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->create();
    GalleryImage::factory()->for($gallery)->create(['sort_order' => 7]);

    $image = app(StoreGalleryImage::class)($gallery, UploadedFile::fake()->image('photo.jpg'));

    expect($image->sort_order)->toBe(8)
        ->and($image->title)->toBeNull();
});

it('skips thumbnail generation when thumbnails are disabled', function () {
    /** @var TestCase $this */
    config(['gallery.thumbnails.enabled' => false]);

    $image = app(StoreGalleryImage::class)(
        Gallery::factory()->create(),
        UploadedFile::fake()->image('photo.jpg', 640, 480),
    );

    expect($image->width)->toBe(640)
        ->and($image->height)->toBe(480)
        ->and($image->thumbnail_path)->toBeNull();

    expect(Storage::disk('public')->allFiles('galleries/thumbs'))->toBeEmpty();
});

it('dispatches a queued job when thumbnails are queued', function () {
    /** @var TestCase $this */
    Bus::fake();
    config(['gallery.thumbnails.queue' => true]);

    $image = app(StoreGalleryImage::class)(
        Gallery::factory()->create(),
        UploadedFile::fake()->image('photo.jpg'),
    );

    expect($image->thumbnail_path)->toBeNull();

    Bus::assertDispatched(
        GenerateGalleryThumbnail::class,
        fn (GenerateGalleryThumbnail $job): bool => $job->image->is($image)
    );
});

it('reports but does not fail the upload when the image cannot be processed', function () {
    /** @var TestCase $this */
    Exceptions::fake();

    $image = app(StoreGalleryImage::class)(
        Gallery::factory()->create(),
        UploadedFile::fake()->createWithContent('photo.jpg', 'not really an image'),
    );

    expect($image->exists)->toBeTrue()
        ->and($image->width)->toBeNull()
        ->and($image->height)->toBeNull()
        ->and($image->thumbnail_path)->toBeNull();

    Storage::disk('public')->assertExists($image->path);
    Exceptions::assertReported(ImageException::class);
});

it('throws when the original cannot be stored', function () {
    /** @var TestCase $this */
    $file = new class(__FILE__, 'photo.jpg') extends UploadedFile
    {
        /**
         * @param  array<string, mixed>  $options
         */
        public function store($path = '', $options = []): string|bool
        {
            return false;
        }
    };

    expect(fn () => app(StoreGalleryImage::class)(Gallery::factory()->create(), $file))
        ->toThrow(RuntimeException::class, 'Failed to store gallery image.');

    expect(GalleryImage::count())->toBe(0);
});

it('removes the stored file when the record cannot be created', function () {
    /** @var TestCase $this */
    GalleryImage::creating(function (): never {
        throw new RuntimeException('Record creation failed.');
    });

    expect(fn () => app(StoreGalleryImage::class)(Gallery::factory()->create(), UploadedFile::fake()->image('photo.jpg')))
        ->toThrow(RuntimeException::class, 'Record creation failed.');

    expect(Storage::disk('public')->allFiles())->toBeEmpty()
        ->and(GalleryImage::count())->toBe(0);
});
