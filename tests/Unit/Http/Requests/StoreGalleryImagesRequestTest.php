<?php

declare(strict_types=1);

use Jgawlik\LaravelGallery\Http\Requests\StoreGalleryImagesRequest;

it('is always authorized', function () {
    $request = new StoreGalleryImagesRequest;

    expect($request->authorize())->toBeTrue();
});

it('validates nested image fields', function () {
    $request = new StoreGalleryImagesRequest;

    $rules = $request->rules();

    expect($rules['images'])->toContain('required', 'array', 'min:1')
        ->and($rules['images.*.file'])->toContain('image', 'mimes:jpg,jpeg,png,webp', 'max:5120')
        ->and($rules['images.*.title'])->toContain('nullable', 'string', 'max:255')
        ->and($rules['images.*.description'])->toContain('nullable', 'string', 'max:65535');
});
