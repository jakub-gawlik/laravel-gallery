<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Policies;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Authenticatable;
use Jgawlik\LaravelGallery\Contracts\Ownable;

/**
 * Any user may list and create their own records; only the owner may view, update or delete one.
 *
 * Route binding is deliberately left unscoped so consumers can expose galleries
 * publicly by registering their own policy via Gate::policy().
 */
abstract class OwnedModelPolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user !== null;
    }

    public function create(?Authenticatable $user): Response
    {
        return $user !== null ? Response::allow() : Response::deny();
    }

    public function view(?Authenticatable $user, Ownable $model): Response
    {
        return $this->owner($user, $model);
    }

    public function update(?Authenticatable $user, Ownable $model): Response
    {
        return $this->owner($user, $model);
    }

    public function delete(?Authenticatable $user, Ownable $model): Response
    {
        return $this->owner($user, $model);
    }

    public function restore(?Authenticatable $user, Ownable $model): Response
    {
        return $this->owner($user, $model);
    }

    public function forceDelete(?Authenticatable $user, Ownable $model): Response
    {
        return $this->owner($user, $model);
    }

    /**
     * Galleries are private by default: respond 404 so other users can't probe which records exist.
     */
    protected function owner(?Authenticatable $user, Ownable $model): Response
    {
        return $user !== null && $model->isOwnedBy($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
