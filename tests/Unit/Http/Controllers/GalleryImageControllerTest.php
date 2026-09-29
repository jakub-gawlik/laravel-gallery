<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Jgawlik\LaravelGallery\Http\Controllers\GalleryImageController;
use Jgawlik\LaravelGallery\Http\Requests\StoreGalleryImagesRequest;
use Jgawlik\LaravelGallery\Models\Gallery;
use Jgawlik\LaravelGallery\Models\GalleryImage;
use Jgawlik\LaravelGallery\Tests\TestCase;

it('throws when storing a gallery image fails', function () {
    /** @var TestCase $this */
    $this->actingAs(galleryUser());
    Storage::fake('public');

    $gallery = Gallery::factory()->create(['user_id' => 1]);

    $file = new class(__FILE__, 'image.jpg') extends UploadedFile
    {
        /**
         * @param  array<string, mixed>  $options
         */
        public function store($path = '', $options = []): string|bool
        {
            return false;
        }
    };

    $request = new class([['file' => $file, 'title' => 'Test']]) extends StoreGalleryImagesRequest
    {
        /**
         * @param  list<array<string, mixed>>  $images
         */
        public function __construct(private array $images)
        {
            parent::__construct();
        }

        public function file($key = null, $default = null): mixed
        {
            if ($key === null || $key === 'images') {
                return array_map(fn (array $image): UploadedFile => $image['file'], $this->images);
            }

            if (preg_match('/^images\.(\d+)\.file$/', (string) $key, $matches)) {
                return $this->images[(int) $matches[1]]['file'] ?? null;
            }

            return parent::file($key, $default);
        }

        public function input($key = null, $default = null): mixed
        {
            if ($key === null || $key === 'images') {
                return array_map(function (array $image): array {
                    unset($image['file']);

                    return $image;
                }, $this->images);
            }

            return parent::input($key, $default);
        }
    };

    $controller = new GalleryImageController;

    expect(fn () => $controller->store($request, $gallery))
        ->toThrow(RuntimeException::class, 'Failed to store gallery image.');
});

it('throws when the uploaded file cannot be resolved', function () {
    /** @var TestCase $this */
    $this->actingAs(galleryUser());
    Storage::fake('public');

    $gallery = Gallery::factory()->create(['user_id' => 1]);

    $request = new class([['title' => 'Missing file']]) extends StoreGalleryImagesRequest
    {
        /**
         * @param  list<array<string, mixed>>  $images
         */
        public function __construct(private array $images)
        {
            parent::__construct();
        }

        public function file($key = null, $default = null): mixed
        {
            if (preg_match('/^images\.\d+\.file$/', (string) $key)) {
                return null;
            }

            return parent::file($key, $default);
        }

        public function input($key = null, $default = null): mixed
        {
            if ($key === null || $key === 'images') {
                return $this->images;
            }

            return parent::input($key, $default);
        }
    };

    $controller = new GalleryImageController;

    expect(fn () => $controller->store($request, $gallery))
        ->toThrow(RuntimeException::class, 'Failed to store gallery image.');
});

it('cleans up already stored files when a later image fails to store', function () {
    /** @var TestCase $this */
    $this->actingAs(galleryUser());
    Storage::fake('public');

    $gallery = Gallery::factory()->create(['user_id' => 1]);

    $existingPath = 'galleries/already-stored.jpg';

    $successfulFile = new class($existingPath, __FILE__, 'one.jpg') extends UploadedFile
    {
        public function __construct(private string $storedPath, string $path, string $originalName)
        {
            parent::__construct($path, $originalName);
        }

        /**
         * @param  string  $path
         * @param  array<string, mixed>|string  $options
         */
        public function store($path = '', $options = []): string
        {
            return $this->storedPath;
        }
    };

    $failingFile = new class(__FILE__, 'two.jpg') extends UploadedFile
    {
        /**
         * @param  array<string, mixed>  $options
         */
        public function store($path = '', $options = []): string|bool
        {
            return false;
        }
    };

    Storage::disk('public')->put($existingPath, 'content');

    $request = new class([['file' => $successfulFile, 'title' => 'One'], ['file' => $failingFile, 'title' => 'Two']]) extends StoreGalleryImagesRequest
    {
        /**
         * @param  list<array<string, mixed>>  $images
         */
        public function __construct(private array $images)
        {
            parent::__construct();
        }

        public function file($key = null, $default = null): mixed
        {
            if ($key === null || $key === 'images') {
                return array_map(fn (array $image): UploadedFile => $image['file'], $this->images);
            }

            if (preg_match('/^images\.(\d+)\.file$/', (string) $key, $matches)) {
                return $this->images[(int) $matches[1]]['file'] ?? null;
            }

            return parent::file($key, $default);
        }

        public function input($key = null, $default = null): mixed
        {
            if ($key === null || $key === 'images') {
                return array_map(function (array $image): array {
                    unset($image['file']);

                    return $image;
                }, $this->images);
            }

            return parent::input($key, $default);
        }
    };

    $controller = new GalleryImageController;

    expect(fn () => $controller->store($request, $gallery))
        ->toThrow(RuntimeException::class, 'Failed to store gallery image.');

    Storage::disk('public')->assertMissing($existingPath);
    expect(GalleryImage::count())->toBe(0);
});
