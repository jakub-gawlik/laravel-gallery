<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Actions;

use Illuminate\Image\Image;
use Illuminate\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Jgawlik\LaravelGallery\Contracts\Thumbnailable;
use RuntimeException;

/**
 * Generate (or regenerate) the thumbnail for a stored image, be it a
 * gallery image or a gallery cover.
 */
class GenerateThumbnail
{
    public function __construct(private readonly ImageManager $images) {}

    /**
     * @template TTarget of Thumbnailable
     *
     * @param  TTarget  $target
     * @param  Image|null  $source  The source image; read back from storage when omitted.
     * @return TTarget
     *
     * @throws RuntimeException When the target has no image or the thumbnail cannot be written.
     */
    public function __invoke(Thumbnailable $target, ?Image $source = null): Thumbnailable
    {
        $disk = $target->diskName();
        $path = $target->sourceImagePath();

        if ($path === null) {
            throw new RuntimeException('There is no image to generate a thumbnail from.');
        }

        $thumbnail = ($source ?? $this->images->fromStorage($path, $disk))
            ->orient()
            ->cover($this->width(), $this->height())
            ->optimize($this->format(), $this->quality());

        $thumbnailPath = $thumbnail->store($this->directory($target), $disk);

        if ($thumbnailPath === false) {
            throw new RuntimeException('Failed to store gallery image thumbnail.');
        }

        $previous = $target->currentThumbnailPath();

        $target->recordThumbnail($thumbnailPath, $thumbnail->width(), $thumbnail->height());

        if ($previous !== null && $previous !== $thumbnailPath) {
            Storage::disk($disk)->delete($previous);
        }

        return $target;
    }

    /**
     * The directory the target's thumbnail is stored in, relative to the disk
     * root: the configured thumbnails sub-directory next to the source image.
     */
    public function directory(Thumbnailable $target): string
    {
        $directory = trim(dirname((string) $target->sourceImagePath()), '/');
        $thumbnails = trim((string) config('gallery.thumbnails.path', 'thumbs'), '/');

        return $directory === '.' || $directory === ''
            ? $thumbnails
            : $directory.'/'.$thumbnails;
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
