<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Contracts;

/**
 * A model owning a stored image that can have a thumbnail generated for it.
 */
interface Thumbnailable
{
    /**
     * The name of the storage disk holding the image and its thumbnail.
     */
    public function diskName(): string;

    /**
     * The path of the source image on the disk, or null when there is none.
     */
    public function sourceImagePath(): ?string;

    /**
     * The path of the current thumbnail on the disk, or null when there is none.
     */
    public function currentThumbnailPath(): ?string;

    /**
     * Persist the path and dimensions of a freshly generated thumbnail.
     */
    public function recordThumbnail(string $path, int $width, int $height): void;
}
