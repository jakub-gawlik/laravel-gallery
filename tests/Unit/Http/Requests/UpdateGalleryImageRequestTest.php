<?php

declare(strict_types=1);

use Jgawlik\LaravelGallery\Http\Requests\UpdateGalleryImageRequest;
use Jgawlik\LaravelGallery\Models\Gallery;
use Jgawlik\LaravelGallery\Models\GalleryImage;

it('denies authorization without a bound image', function () {
    $request = new UpdateGalleryImageRequest;
    $request->setRouteResolver(fn () => null);

    expect($request->authorize()->denied())->toBeTrue();
});

it('authorizes through the update gate for the bound image', function () {
    $gallery = Gallery::factory()->create(['user_id' => 1]);
    $image = GalleryImage::factory()->for($gallery)->create();

    $request = UpdateGalleryImageRequest::create("/api/v1/galleries/{$gallery->id}/images/{$image->id}", 'PUT');
    $request->setUserResolver(fn () => galleryUser());
    $request->setRouteResolver(fn () => new class($image)
    {
        public function __construct(private GalleryImage $image) {}

        public function parameter(string $name): GalleryImage
        {
            return $this->image;
        }
    });

    auth()->setUser(galleryUser());

    expect($request->authorize()->allowed())->toBeTrue();
});
