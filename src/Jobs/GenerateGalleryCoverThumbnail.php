<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Jgawlik\LaravelGallery\Actions\GenerateThumbnail;
use Jgawlik\LaravelGallery\Models\Gallery;

class GenerateGalleryCoverThumbnail implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Delete the job if the gallery has been deleted in the meantime.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(public Gallery $gallery) {}

    public function handle(GenerateThumbnail $generateThumbnail): void
    {
        if ($this->gallery->image_path === null) {
            return;
        }

        $generateThumbnail($this->gallery);
    }
}
