<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Policies;

use Jgawlik\LaravelGallery\Concerns\OwnedByUser;
use Jgawlik\LaravelGallery\Models\Gallery;
use Jgawlik\LaravelGallery\Models\GalleryImage;
use Illuminate\Contracts\Auth\Authenticatable;

class GalleryImagePolicy
{
    use OwnedByUser;

    public function viewAny(?Authenticatable $user): bool
    {
        return $user !== null;
    }

    public function view(?Authenticatable $user, GalleryImage $image): bool
    {
        return $this->ownsImage($user, $image);
    }

    public function create(?Authenticatable $user, Gallery $gallery): bool
    {
        return $this->ownsGallery($user, $gallery);
    }

    public function update(?Authenticatable $user, GalleryImage $image): bool
    {
        return $this->ownsImage($user, $image);
    }

    public function delete(?Authenticatable $user, GalleryImage $image): bool
    {
        return $this->ownsImage($user, $image);
    }

    public function restore(?Authenticatable $user, GalleryImage $image): bool
    {
        return $this->ownsImage($user, $image);
    }

    public function forceDelete(?Authenticatable $user, GalleryImage $image): bool
    {
        return $this->ownsImage($user, $image);
    }

    private function ownsGallery(?Authenticatable $user, Gallery $gallery): bool
    {
        return $this->ownsModel($user, $gallery);
    }

    private function ownsImage(?Authenticatable $user, GalleryImage $image): bool
    {
        return $this->ownsGallery($user, $image->gallery);
    }
}
