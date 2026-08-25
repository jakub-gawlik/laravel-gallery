<?php

declare(strict_types=1);

use Jgawlik\LaravelGallery\Models\Gallery;
use Jgawlik\LaravelGallery\Models\GalleryImage;
use Jgawlik\LaravelGallery\Policies\GalleryImagePolicy;

it('allows viewing any gallery image for an authenticated user', function () {
    $policy = new GalleryImagePolicy;

    expect($policy->viewAny(galleryUser()))->toBeTrue();
});

it('denies viewing any gallery image for a guest', function () {
    $policy = new GalleryImagePolicy;

    expect($policy->viewAny(null))->toBeFalse();
});

it('denies viewing a gallery image owned by another user', function () {
    $image = GalleryImage::factory()->for(Gallery::factory()->state(['user_id' => 2]))->create();
    $policy = new GalleryImagePolicy;

    expect($policy->view(galleryUser(), $image))->toBeFalse();
});

it('allows viewing a gallery image owned by the current user', function () {
    $image = GalleryImage::factory()->for(Gallery::factory()->state(['user_id' => 1]))->create();
    $policy = new GalleryImagePolicy;

    expect($policy->view(galleryUser(), $image))->toBeTrue();
});

it('allows creating a gallery image for a gallery owned by the current user', function () {
    $gallery = Gallery::factory()->create(['user_id' => 1]);
    $policy = new GalleryImagePolicy;

    expect($policy->create(galleryUser(), $gallery))->toBeTrue();
});

it('denies creating a gallery image for a guest', function () {
    $gallery = Gallery::factory()->create(['user_id' => 1]);
    $policy = new GalleryImagePolicy;

    expect($policy->create(null, $gallery))->toBeFalse();
});

it('denies creating a gallery image for another user gallery', function () {
    $gallery = Gallery::factory()->create(['user_id' => 2]);
    $policy = new GalleryImagePolicy;

    expect($policy->create(galleryUser(), $gallery))->toBeFalse();
});

it('allows updating a gallery image owned by the current user', function () {
    $image = GalleryImage::factory()->for(Gallery::factory()->state(['user_id' => 1]))->create();
    $policy = new GalleryImagePolicy;

    expect($policy->update(galleryUser(), $image))->toBeTrue();
});

it('allows deleting a gallery image owned by the current user', function () {
    $image = GalleryImage::factory()->for(Gallery::factory()->state(['user_id' => 1]))->create();
    $policy = new GalleryImagePolicy;

    expect($policy->delete(galleryUser(), $image))->toBeTrue();
});

it('allows restoring a gallery image owned by the current user', function () {
    $image = GalleryImage::factory()->for(Gallery::factory()->state(['user_id' => 1]))->create();
    $policy = new GalleryImagePolicy;

    expect($policy->restore(galleryUser(), $image))->toBeTrue();
});

it('allows force deleting a gallery image owned by the current user', function () {
    $image = GalleryImage::factory()->for(Gallery::factory()->state(['user_id' => 1]))->create();
    $policy = new GalleryImagePolicy;

    expect($policy->forceDelete(galleryUser(), $image))->toBeTrue();
});
