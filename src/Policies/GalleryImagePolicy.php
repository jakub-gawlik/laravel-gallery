<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Policies;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Authenticatable;
use Jgawlik\LaravelGallery\Models\Gallery;

class GalleryImagePolicy extends OwnedModelPolicy
{
    /**
     * Images are created into a parent gallery, which the user must own.
     */
    public function create(?Authenticatable $user, ?Gallery $gallery = null): Response
    {
        return $gallery !== null ? $this->owner($user, $gallery) : Response::deny();
    }
}
