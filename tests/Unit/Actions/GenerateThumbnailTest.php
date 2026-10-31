<?php

declare(strict_types=1);

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Image\ImageException;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Jgawlik\LaravelGallery\Actions\GenerateThumbnail;
use Jgawlik\LaravelGallery\Models\GalleryImage;
use Jgawlik\LaravelGallery\Tests\TestCase;

function fakeImageBytes(int $width = 1600, int $height = 900, string $name = 'photo.jpg'): string
{
    return UploadedFile::fake()->image($name, $width, $height)->getContent();
}

beforeEach(function () {
    Storage::fake('public');
});

it('generates a thumbnail from the stored original', function () {
    /** @var TestCase $this */
    $image = GalleryImage::factory()->create(['path' => 'galleries/photo.jpg']);
    Storage::disk('public')->put('galleries/photo.jpg', fakeImageBytes());

    $result = app(GenerateThumbnail::class)($image);

    expect($result)->toBe($image)
        ->and($image->thumbnail_path)->toStartWith('galleries/thumbs/')
        ->and($image->thumbnail_path)->toEndWith('.webp')
        ->and($image->thumbnail_width)->toBe(800)
        ->and($image->thumbnail_height)->toBe(450)
        ->and($image->fresh()?->thumbnail_path)->toBe($image->thumbnail_path);

    $bytes = Storage::disk('public')->get((string) $image->thumbnail_path);

    expect((new finfo(FILEINFO_MIME_TYPE))->buffer((string) $bytes))->toBe('image/webp');
});

it('honours the configured dimensions and format', function () {
    /** @var TestCase $this */
    config([
        'gallery.thumbnails.width' => 300,
        'gallery.thumbnails.height' => 200,
        'gallery.thumbnails.format' => 'jpg',
        'gallery.thumbnails.quality' => 60,
        'gallery.thumbnails.path' => 'previews',
    ]);

    $image = GalleryImage::factory()->create(['path' => 'galleries/photo.png']);
    Storage::disk('public')->put('galleries/photo.png', fakeImageBytes(400, 400, 'photo.png'));

    app(GenerateThumbnail::class)($image);

    expect($image->thumbnail_path)->toStartWith('galleries/previews/')
        ->and($image->thumbnail_path)->toEndWith('.jpg')
        ->and($image->thumbnail_width)->toBe(300)
        ->and($image->thumbnail_height)->toBe(200);

    $size = getimagesizefromstring((string) Storage::disk('public')->get((string) $image->thumbnail_path));

    expect($size)->toMatchArray([0 => 300, 1 => 200, 'mime' => 'image/jpeg']);
});

it('uses the given source image instead of reading from storage', function () {
    /** @var TestCase $this */
    $image = GalleryImage::factory()->create(['path' => 'galleries/missing.jpg']);

    app(GenerateThumbnail::class)($image, Image::fromBytes(fakeImageBytes(1000, 500)));

    expect($image->thumbnail_path)->not->toBeNull();
    Storage::disk('public')->assertExists((string) $image->thumbnail_path);
});

it('replaces an existing thumbnail and removes the old file', function () {
    /** @var TestCase $this */
    $image = GalleryImage::factory()->withThumbnail()->create([
        'path' => 'galleries/photo.jpg',
        'thumbnail_path' => 'galleries/thumbs/old.webp',
    ]);
    Storage::disk('public')->put('galleries/photo.jpg', fakeImageBytes());
    Storage::disk('public')->put('galleries/thumbs/old.webp', 'old');

    app(GenerateThumbnail::class)($image);

    expect($image->thumbnail_path)->not->toBe('galleries/thumbs/old.webp');
    Storage::disk('public')->assertMissing('galleries/thumbs/old.webp');
    Storage::disk('public')->assertExists((string) $image->thumbnail_path);
});

it('throws when the original is not a valid image', function () {
    /** @var TestCase $this */
    $image = GalleryImage::factory()->create(['path' => 'galleries/photo.jpg']);
    Storage::disk('public')->put('galleries/photo.jpg', 'not an image');

    expect(fn () => app(GenerateThumbnail::class)($image))->toThrow(ImageException::class);

    expect($image->fresh()?->thumbnail_path)->toBeNull();
});

it('throws when the thumbnail cannot be written to the disk', function () {
    /** @var TestCase $this */
    $disk = Mockery::mock(Filesystem::class);
    $disk->shouldReceive('get')->with('galleries/photo.jpg')->andReturn(fakeImageBytes());
    $disk->shouldReceive('put')->andReturn(false);
    Storage::set('public', $disk);

    $image = GalleryImage::factory()->create(['path' => 'galleries/photo.jpg']);

    expect(fn () => app(GenerateThumbnail::class)($image))
        ->toThrow(RuntimeException::class, 'Failed to store gallery image thumbnail.');

    expect($image->fresh()?->thumbnail_path)->toBeNull();
});

it('builds the thumbnail directory from the gallery path', function () {
    config(['gallery.path' => '/uploads/galleries/', 'gallery.thumbnails.path' => '/small/']);

    expect(app(GenerateThumbnail::class)->directory())->toBe('uploads/galleries/small');
});
