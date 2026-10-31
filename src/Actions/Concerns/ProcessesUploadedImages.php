<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Actions\Concerns;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Image\ImageManager;
use Jgawlik\LaravelGallery\Actions\GenerateThumbnail;
use Jgawlik\LaravelGallery\Contracts\Thumbnailable;
use Throwable;

/**
 * Shared upload behaviour for the store actions: where uploads go, how their
 * dimensions are detected and how thumbnails are generated.
 *
 * @property-read ImageManager $images
 * @property-read GenerateThumbnail $generateThumbnail
 */
trait ProcessesUploadedImages
{
    /**
     * The directory uploads are stored in, relative to the disk root: the
     * configured gallery path, optionally extended with a sub-directory.
     */
    protected function uploadDirectory(?string $directory = null): string
    {
        $path = trim((string) config('gallery.path', 'galleries'), '/');
        $directory = trim((string) $directory, '/');

        return $directory === '' ? $path : $path.'/'.$directory;
    }

    /**
     * Detect the (orientation-corrected) dimensions of the uploaded image.
     *
     * @return array{0: int|null, 1: int|null}
     */
    protected function dimensions(UploadedFile $file): array
    {
        try {
            return $this->images->driver()->dimensions($file->getContent());
        } catch (Throwable $e) {
            report($e);

            return [null, null];
        }
    }

    /**
     * Generate the thumbnail inline, or hand it to the queue through the given
     * dispatcher, as configured. Inline failures are reported, not thrown.
     *
     * @param  Closure(): mixed  $dispatch
     */
    protected function thumbnail(Thumbnailable $target, UploadedFile $file, Closure $dispatch): void
    {
        if (! config('gallery.thumbnails.enabled', true)) {
            return;
        }

        if (config('gallery.thumbnails.queue', false)) {
            $dispatch();

            return;
        }

        try {
            ($this->generateThumbnail)($target, $this->images->fromUpload($file));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
