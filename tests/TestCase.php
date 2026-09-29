<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jgawlik\LaravelGallery\GalleryServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [GalleryServiceProvider::class];
    }
}
