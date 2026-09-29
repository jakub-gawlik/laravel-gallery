<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;

trait OwnedByUser
{
    /**
     * Check if the given user owns the model (which must have a user_id column).
     */
    protected function ownsModel(Authenticatable $user, mixed $model): bool
    {
        return (string) $model->user_id === (string) $user->getAuthIdentifier();
    }

    /**
     * Extract user ID from an Authenticatable, int, string, or null.
     */
    protected function extractUserId(Authenticatable|int|string|null $user): int|string|null
    {
        if ($user === null) {
            return null;
        }

        return $user instanceof Authenticatable ? $user->getAuthIdentifier() : $user;
    }
}
