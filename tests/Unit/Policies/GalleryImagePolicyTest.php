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

it('denies viewing a gallery image owned by another user as not found', function () {
    $image = GalleryImage::factory()->for(Gallery::factory()->state(['user_id' => 2]))->create();
    $policy = new GalleryImagePolicy;

    $response = $policy->view(galleryUser(), $image);

    expect($response->denied())->toBeTrue()
        ->and($response->status())->toBe(404);
});

it('allows viewing a gallery image owned by the current user', function () {
    $image = GalleryImage::factory()->for(Gallery::factory()->state(['user_id' => 1]))->create();
    $policy = new GalleryImagePolicy;

    expect($policy->view(galleryUser(), $image)->allowed())->toBeTrue();
});

it('allows creating a gallery image for a gallery owned by the current user', function () {
    $gallery = Gallery::factory()->create(['user_id' => 1]);
    $policy = new GalleryImagePolicy;

    expect($policy->create(galleryUser(), $gallery)->allowed())->toBeTrue();
});

it('denies creating a gallery image for a guest', function () {
    $gallery = Gallery::factory()->create(['user_id' => 1]);
    $policy = new GalleryImagePolicy;

    expect($policy->create(null, $gallery)->denied())->toBeTrue();
});

it('denies creating a gallery image for another user gallery as not found', function () {
    $gallery = Gallery::factory()->create(['user_id' => 2]);
    $policy = new GalleryImagePolicy;

    $response = $policy->create(galleryUser(), $gallery);

    expect($response->denied())->toBeTrue()
        ->and($response->status())->toBe(404);
});

it('denies creating a gallery image without a parent gallery', function () {
    $policy = new GalleryImagePolicy;

    expect($policy->create(galleryUser())->denied())->toBeTrue();
});

it('allows updating a gallery image owned by the current user', function () {
    $image = GalleryImage::factory()->for(Gallery::factory()->state(['user_id' => 1]))->create();
    $policy = new GalleryImagePolicy;

    expect($policy->update(galleryUser(), $image)->allowed())->toBeTrue();
});

it('allows deleting a gallery image owned by the current user', function () {
    $image = GalleryImage::factory()->for(Gallery::factory()->state(['user_id' => 1]))->create();
    $policy = new GalleryImagePolicy;

    expect($policy->delete(galleryUser(), $image)->allowed())->toBeTrue();
});

it('allows restoring a gallery image owned by the current user', function () {
    $image = GalleryImage::factory()->for(Gallery::factory()->state(['user_id' => 1]))->create();
    $policy = new GalleryImagePolicy;

    expect($policy->restore(galleryUser(), $image)->allowed())->toBeTrue();
});

it('allows force deleting a gallery image owned by the current user', function () {
    $image = GalleryImage::factory()->for(Gallery::factory()->state(['user_id' => 1]))->create();
    $policy = new GalleryImagePolicy;

    expect($policy->forceDelete(galleryUser(), $image)->allowed())->toBeTrue();
});
