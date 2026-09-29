<?php

declare(strict_types=1);

use Illuminate\Validation\Rules\Unique;
use Jgawlik\LaravelGallery\Http\Requests\StoreGalleryRequest;

it('is always authorized', function () {
    $request = new StoreGalleryRequest;

    expect($request->authorize())->toBeTrue();
});

it('requires a name', function () {
    $request = new StoreGalleryRequest;

    $rules = $request->rules();

    expect($rules['name'])->toContain('required')
        ->and($rules['slug'])->toHaveCount(4)
        ->and($rules['slug'][3])->toBeInstanceOf(Unique::class)
        ->and($rules['description'])->toContain('nullable')
        ->and($rules['image'])->toContain('image');
});
