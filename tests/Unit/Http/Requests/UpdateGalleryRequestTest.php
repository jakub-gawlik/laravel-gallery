<?php

declare(strict_types=1);

use Jgawlik\LaravelGallery\Http\Requests\UpdateGalleryRequest;
use Jgawlik\LaravelGallery\Models\Gallery;
use Illuminate\Validation\Rules\Unique;

it('is always authorized', function () {
    $request = new UpdateGalleryRequest;

    expect($request->authorize())->toBeTrue();
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
