<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Policies;

use Jgawlik\LaravelGallery\Concerns\OwnedByUser;
use Jgawlik\LaravelGallery\Models\Gallery;
use Illuminate\Contracts\Auth\Authenticatable;

class GalleryPolicy
{
    use OwnedByUser;

    public function viewAny(?Authenticatable $user): bool
    {
        return $user !== null;
    }

    public function view(?Authenticatable $user, Gallery $gallery): bool
    {
        return $this->ownsGallery($user, $gallery);
    }

    public function create(?Authenticatable $user): bool
    {
        return $user !== null;
    }

    public function update(?Authenticatable $user, Gallery $gallery): bool
    {
        return $this->ownsGallery($user, $gallery);
    }

    public function delete(?Authenticatable $user, Gallery $gallery): bool
    {
        return $this->ownsGallery($user, $gallery);
    }

    public function restore(?Authenticatable $user, Gallery $gallery): bool
    {
        return $this->ownsGallery($user, $gallery);
    }

    public function forceDelete(?Authenticatable $user, Gallery $gallery): bool
    {
        return $this->ownsGallery($user, $gallery);
    }

    private function ownsGallery(?Authenticatable $user, Gallery $gallery): bool
    {
        return $this->ownsModel($user, $gallery);
    }
}
