<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Actions;

use Illuminate\Image\Image;
use Illuminate\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Jgawlik\LaravelGallery\Models\GalleryImage;
use RuntimeException;

/**
 * Generate (or regenerate) the thumbnail for a stored gallery image.
 */
class GenerateThumbnail
{
    public function __construct(private readonly ImageManager $images) {}

    /**
     * @param  Image|null  $source  The source image; read back from storage when omitted.
     */
    public function __invoke(GalleryImage $image, ?Image $source = null): GalleryImage
    {
        $disk = $image->diskName();

        $thumbnail = ($source ?? $this->images->fromStorage($image->path, $disk))
            ->orient()
            ->cover($this->width(), $this->height())
            ->optimize($this->format(), $this->quality());

        $thumbnailPath = $thumbnail->store($this->directory(), $disk);

        if ($thumbnailPath === false) {
            throw new RuntimeException('Failed to store gallery image thumbnail.');
        }

        $previous = $image->thumbnail_path;

        $image->forceFill([
            'thumbnail_path' => $thumbnailPath,
            'thumbnail_width' => $thumbnail->width(),
            'thumbnail_height' => $thumbnail->height(),
        ])->save();

        if ($previous !== null && $previous !== $thumbnailPath) {
            Storage::disk($disk)->delete($previous);
        }

        return $image;
    }

    /**
     * The directory thumbnails are stored in, relative to the disk root.
     */
    public function directory(): string
    {
        return trim((string) config('gallery.path', 'galleries'), '/')
            .'/'.trim((string) config('gallery.thumbnails.path', 'thumbs'), '/');
    }

    /**
     * @return int<1, max>
     */
    private function width(): int
    {
        return max(1, (int) config('gallery.thumbnails.width', 800));
    }

    /**
     * @return int<1, max>
     */
    private function height(): int
    {
        return max(1, (int) config('gallery.thumbnails.height', 450));
    }

    private function format(): string
    {
        return (string) config('gallery.thumbnails.format', 'webp');
    }

    private function quality(): int
    {
        return (int) config('gallery.thumbnails.quality', 75);
    }
}
