<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Jgawlik\LaravelGallery\Actions\GenerateThumbnail;
use Jgawlik\LaravelGallery\Models\GalleryImage;

class GenerateGalleryThumbnail implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Delete the job if the image has been deleted in the meantime.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(public GalleryImage $image) {}

    public function handle(GenerateThumbnail $generateThumbnail): void
    {
        $generateThumbnail($this->image);
    }
}
