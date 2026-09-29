<?php

declare(strict_types=1);

use Illuminate\Validation\Rules\Unique;
use Jgawlik\LaravelGallery\Http\Requests\UpdateGalleryRequest;
use Jgawlik\LaravelGallery\Models\Gallery;

it('denies authorization without a bound gallery', function () {
    $request = new UpdateGalleryRequest;
    $request->setRouteResolver(fn () => null);

    expect($request->authorize()->denied())->toBeTrue();
});

it('authorizes through the update gate for the bound gallery', function () {
    $gallery = Gallery::factory()->create(['user_id' => 1]);

    $request = UpdateGalleryRequest::create("/api/v1/galleries/{$gallery->id}", 'PUT');
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

it('ignores the current gallery when validating slug uniqueness', function () {
    $gallery = Gallery::factory()->create();

    $request = new UpdateGalleryRequest;
    $request->setRouteResolver(fn () => new class($gallery)
    {
        public function __construct(private Gallery $gallery) {}

        public function parameter(string $name): Gallery
        {
            return $this->gallery;
        }
    });

    $rules = $request->rules();

    expect($rules['slug'][3])->toBeInstanceOf(Unique::class);
});
