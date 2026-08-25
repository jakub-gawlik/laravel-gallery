<?php

declare(strict_types=1);

use Jgawlik\LaravelGallery\Http\Controllers\GalleryController;
use Jgawlik\LaravelGallery\Http\Controllers\GalleryImageController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(config('gallery.middleware', ['api', 'auth']))->group(function (): void {
    Route::apiResource('galleries', GalleryController::class);

    Route::resource('galleries.images', GalleryImageController::class)->only(['store', 'destroy']);
});
