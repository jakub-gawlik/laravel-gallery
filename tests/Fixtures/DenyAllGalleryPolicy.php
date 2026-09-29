<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Tests\Fixtures;

use Illuminate\Contracts\Auth\Authenticatable;
use Jgawlik\LaravelGallery\Models\Gallery;

class DenyAllGalleryPolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return false;
    }

    public function view(?Authenticatable $user, Gallery $gallery): bool
    {
        return false;
    }

    public function create(?Authenticatable $user): bool
    {
        return false;
    }

    public function update(?Authenticatable $user, Gallery $gallery): bool
    {
        return false;
    }

    public function delete(?Authenticatable $user, Gallery $gallery): bool
    {
        return false;
    }
}
