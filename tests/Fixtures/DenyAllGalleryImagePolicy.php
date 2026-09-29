<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Tests\Fixtures;

use Illuminate\Contracts\Auth\Authenticatable;
use Jgawlik\LaravelGallery\Models\Gallery;
use Jgawlik\LaravelGallery\Models\GalleryImage;

class DenyAllGalleryImagePolicy
{
    public function create(?Authenticatable $user, Gallery $gallery): bool
    {
        return false;
    }

    public function delete(?Authenticatable $user, GalleryImage $image): bool
    {
        return false;
    }
}
