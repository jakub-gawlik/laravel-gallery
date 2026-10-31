<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Jgawlik\LaravelGallery\Actions\Concerns\ProcessesUploadedImages;
use Jgawlik\LaravelGallery\Jobs\GenerateGalleryCoverThumbnail;
use Jgawlik\LaravelGallery\Models\Gallery;
use RuntimeException;
use Throwable;

/**
 * Store an uploaded file as a gallery's cover image, replacing (and deleting)
 * the previous cover and its thumbnail, record its dimensions and generate
 * its thumbnail (inline or through a queued job, per config).
 *
 * Thumbnail and dimension detection failures are reported but do not fail
 * the upload; `php artisan gallery:thumbnails` can fill in the gaps later.
 */
class StoreGalleryCover
{
    use ProcessesUploadedImages;

    public function __construct(
        private readonly ImageManager $images,
        private readonly GenerateThumbnail $generateThumbnail,
    ) {}

    /**
     * @param  string|null  $directory  Sub-directory of the configured gallery path to store the file in.
     *
     * @throws RuntimeException When the file cannot be written to the disk.
     */
    public function __invoke(Gallery $gallery, UploadedFile $file, ?string $directory = null): Gallery
    {
        $disk = $gallery->diskName();

        $filePath = $file->store($this->uploadDirectory($directory), $disk);

        if ($filePath === false) {
            throw new RuntimeException('Failed to store gallery cover.');
        }

        $previous = array_filter([$gallery->image_path, $gallery->image_thumbnail_path]);

        try {
            [$width, $height] = $this->dimensions($file);

            $gallery->fill([
                'image_path' => $filePath,
                'image_width' => $width,
                'image_height' => $height,
                'image_thumbnail_path' => null,
                'image_thumbnail_width' => null,
                'image_thumbnail_height' => null,
            ])->save();
        } catch (Throwable $e) {
            Storage::disk($disk)->delete($filePath);

            throw $e;
        }

        foreach ($previous as $path) {
            Storage::disk($disk)->delete($path);
        }

        $this->thumbnail($gallery, $file, fn () => GenerateGalleryCoverThumbnail::dispatch($gallery)->afterCommit());

        return $gallery;
    }
}
