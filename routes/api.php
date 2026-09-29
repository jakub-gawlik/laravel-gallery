<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Jgawlik\LaravelGallery\Http\Controllers\GalleryController;
use Jgawlik\LaravelGallery\Http\Controllers\GalleryImageController;

Route::prefix('api/v1')->middleware(config('gallery.middleware', ['api', 'auth']))->group(function (): void {
    Route::apiResource('galleries', GalleryController::class);

    Route::resource('galleries.images', GalleryImageController::class)->only(['store', 'destroy']);
});
