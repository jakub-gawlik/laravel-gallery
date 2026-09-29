<?php

declare(strict_types=1);

use Jgawlik\LaravelGallery\Http\Requests\StoreGalleryImagesRequest;
use Jgawlik\LaravelGallery\Models\Gallery;

it('denies authorization without a bound gallery', function () {
    $request = new StoreGalleryImagesRequest;
    $request->setRouteResolver(fn () => null);

    expect($request->authorize()->denied())->toBeTrue();
});

it('authorizes through the create gate for the bound gallery', function () {
    $gallery = Gallery::factory()->create(['user_id' => 1]);

    $request = StoreGalleryImagesRequest::create("/api/v1/galleries/{$gallery->id}/images", 'POST');
    $request->setUserResolver(fn () => galleryUser());
    $request->setRouteResolver(fn () => new class($gallery)
    {
        public function __construct(private Gallery $gallery) {}

        public function parameter(string $name): Gallery
        {
            return $this->gallery;
        }
    });

    auth()->setUser(galleryUser());

    expect($request->authorize()->allowed())->toBeTrue();
});

it('validates nested image fields', function () {
    $request = new StoreGalleryImagesRequest;

    $rules = $request->rules();

    expect($rules['images'])->toContain('required', 'array', 'min:1')
        ->and($rules['images.*.file'])->toContain('image', 'mimes:jpg,jpeg,png,webp', 'max:5120')
        ->and($rules['images.*.title'])->toContain('nullable', 'string', 'max:255')
        ->and($rules['images.*.description'])->toContain('nullable', 'string', 'max:65535');
});
