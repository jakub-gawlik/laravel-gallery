<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Jgawlik\LaravelGallery\Actions\Concerns\ProcessesUploadedImages;
use Jgawlik\LaravelGallery\Jobs\GenerateGalleryThumbnail;
use Jgawlik\LaravelGallery\Models\Gallery;
use Jgawlik\LaravelGallery\Models\GalleryImage;
use RuntimeException;
use Throwable;

/**
 * Store an uploaded file as a gallery image, record its dimensions and
 * generate its thumbnail (inline or through a queued job, per config).
 *
 * Thumbnail and dimension detection failures are reported but do not fail
 * the upload; `php artisan gallery:thumbnails` can fill in the gaps later.
 */
class StoreGalleryImage
{
    use ProcessesUploadedImages;

    public function __construct(
        private readonly ImageManager $images,
        private readonly GenerateThumbnail $generateThumbnail,
    ) {}

    /**
     * @param  array{title?: string|null, description?: string|null, alt_text?: string|null, sort_order?: int}  $attributes
     * @param  string|null  $directory  Sub-directory of the configured gallery path to store the file in.
     *
     * @throws RuntimeException When the original file cannot be written to the disk.
     */
    public function __invoke(
        Gallery $gallery,
        UploadedFile $file,
        array $attributes = [],
        ?string $directory = null,
    ): GalleryImage {
        $disk = (string) config('gallery.disk', 'public');

        $filePath = $file->store($this->uploadDirectory($directory), $disk);

        if ($filePath === false) {
            throw new RuntimeException('Failed to store gallery image.');
        }

        try {
            [$width, $height] = $this->dimensions($file);

            $image = $gallery->images()->create([
                'path' => $filePath,
                'disk' => $disk,
                'width' => $width,
                'height' => $height,
                'title' => $attributes['title'] ?? null,
                'description' => $attributes['description'] ?? null,
                'alt_text' => $attributes['alt_text'] ?? null,
                'sort_order' => $attributes['sort_order'] ?? ((int) $gallery->images()->max('sort_order') + 1),
            ]);

            $this->thumbnail($image, $file, fn () => GenerateGalleryThumbnail::dispatch($image)->afterCommit());
        } catch (Throwable $e) {
            Storage::disk($disk)->delete($filePath);

            throw $e;
        }

        return $image;
    }
}
