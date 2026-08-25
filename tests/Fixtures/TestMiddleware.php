<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Tests\Fixtures;

use Closure;
use Illuminate\Http\Request;

class TestMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);
        $response->headers->set('X-Test-Middleware', 'applied');

        return $response;
    }
}
