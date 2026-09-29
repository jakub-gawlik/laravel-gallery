<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * A gallery record that belongs to a single user.
 */
interface Ownable
{
    public function isOwnedBy(Authenticatable $user): bool;
}
