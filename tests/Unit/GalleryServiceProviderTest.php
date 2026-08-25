<?php

declare(strict_types=1);

use Jgawlik\LaravelGallery\GalleryServiceProvider;
use Jgawlik\LaravelGallery\Models\Gallery;
use Jgawlik\LaravelGallery\Models\GalleryImage;
use Jgawlik\LaravelGallery\Policies\GalleryImagePolicy;
use Jgawlik\LaravelGallery\Policies\GalleryPolicy;
use Jgawlik\LaravelGallery\Tests\Fixtures\TestMiddleware;
use Jgawlik\LaravelGallery\Tests\TestCase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

it('merges the gallery config', function () {
    /** @var TestCase $this */
    expect(config('gallery.disk'))->toBe('public')
        ->and(config('gallery.path'))->toBe('galleries')
        ->and(config('gallery.middleware'))->toBe(['api', 'auth']);
});

it('loads the package routes', function () {
    /** @var TestCase $this */
    $routes = collect(Route::getRoutes()->getRoutes())->map(fn ($route) => $route->uri());

    expect($routes)->toContain('api/v1/galleries')
        ->and($routes)->toContain('api/v1/galleries/{gallery}')
        ->and($routes)->toContain('api/v1/galleries/{gallery}/images')
        ->and($routes)->toContain('api/v1/galleries/{gallery}/images/{image}');
});

it('registers gallery policies', function () {
    /** @var TestCase $this */
    expect(Gate::getPolicyFor(Gallery::class))->toBeInstanceOf(GalleryPolicy::class)
        ->and(Gate::getPolicyFor(GalleryImage::class))->toBeInstanceOf(GalleryImagePolicy::class);
});

it('applies the default api middleware to gallery routes', function () {
    /** @var TestCase $this */
    $route = collect(Route::getRoutes()->getRoutes())
        ->first(fn ($route) => $route->uri() === 'api/v1/galleries');

    expect($route)->not->toBeNull()
        ->and($route->gatherMiddleware())->toContain('api')
        ->and($route->gatherMiddleware())->toContain('auth');
});

it('applies custom middleware configured in gallery.middleware', function () {
    /** @var TestCase $this */
    config(['gallery.middleware' => ['api', TestMiddleware::class]]);

    Route::middleware(config('gallery.middleware'))->get('/test-galleries', fn () => response()->json([]));

    $this->getJson('/test-galleries')
        ->assertOk()
        ->assertHeader('X-Test-Middleware', 'applied');
});

it('registers config for publishing', function () {
    /** @var TestCase $this */
    $app = app();

    $reflection = new ReflectionProperty($app, 'isRunningInConsole');
    $reflection->setValue($app, true);

    $provider = new GalleryServiceProvider($app);
    $provider->boot();

    expect($provider::$publishes)->toHaveKey(GalleryServiceProvider::class)
        ->and($provider::$publishGroups)->toHaveKey('gallery-config');
});
