<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery;

use Jgawlik\LaravelGallery\Models\Gallery;
use Jgawlik\LaravelGallery\Models\GalleryImage;
use Jgawlik\LaravelGallery\Policies\GalleryImagePolicy;
use Jgawlik\LaravelGallery\Policies\GalleryPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class GalleryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/gallery.php',
            'gallery'
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Gate::policy(Gallery::class, GalleryPolicy::class);
        Gate::policy(GalleryImage::class, GalleryImagePolicy::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/gallery.php' => $this->app->configPath('gallery.php'),
            ], 'gallery-config');
        }
    }
}
