<?php

declare(strict_types=1);

use Jgawlik\LaravelGallery\Models\Gallery;
use Jgawlik\LaravelGallery\Policies\GalleryPolicy;

it('allows viewing any gallery for an authenticated user', function () {
    $policy = new GalleryPolicy;

    expect($policy->viewAny(galleryUser()))->toBeTrue();
});

it('denies viewing any gallery for a guest', function () {
    $policy = new GalleryPolicy;

    expect($policy->viewAny(null))->toBeFalse();
});

it('denies viewing a gallery owned by another user', function () {
    $gallery = Gallery::factory()->create(['user_id' => 2]);
    $policy = new GalleryPolicy;

    expect($policy->view(galleryUser(), $gallery))->toBeFalse();
});

it('allows viewing a gallery owned by the current user', function () {
    $gallery = Gallery::factory()->create(['user_id' => 1]);
    $policy = new GalleryPolicy;

    expect($policy->view(galleryUser(), $gallery))->toBeTrue();
});

it('allows creating a gallery for an authenticated user', function () {
    $policy = new GalleryPolicy;

    expect($policy->create(galleryUser()))->toBeTrue();
});

it('denies creating a gallery for a guest', function () {
    $policy = new GalleryPolicy;

    expect($policy->create(null))->toBeFalse();
});

it('allows updating a gallery owned by the current user', function () {
    $gallery = Gallery::factory()->create(['user_id' => 1]);
    $policy = new GalleryPolicy;

    expect($policy->update(galleryUser(), $gallery))->toBeTrue();
});

it('denies updating a gallery owned by another user', function () {
    $gallery = Gallery::factory()->create(['user_id' => 2]);
    $policy = new GalleryPolicy;

    expect($policy->update(galleryUser(), $gallery))->toBeFalse();
});

it('denies resource actions for a guest', function () {
    $gallery = Gallery::factory()->create(['user_id' => 1]);
    $policy = new GalleryPolicy;

    expect($policy->view(null, $gallery))->toBeFalse()
        ->and($policy->update(null, $gallery))->toBeFalse()
        ->and($policy->delete(null, $gallery))->toBeFalse()
        ->and($policy->restore(null, $gallery))->toBeFalse()
        ->and($policy->forceDelete(null, $gallery))->toBeFalse();
});

it('allows deleting a gallery owned by the current user', function () {
    $gallery = Gallery::factory()->create(['user_id' => 1]);
    $policy = new GalleryPolicy;

    expect($policy->delete(galleryUser(), $gallery))->toBeTrue();
});

it('allows restoring a gallery owned by the current user', function () {
    $gallery = Gallery::factory()->create(['user_id' => 1]);
    $policy = new GalleryPolicy;

    expect($policy->restore(galleryUser(), $gallery))->toBeTrue();
});

it('allows force deleting a gallery owned by the current user', function () {
    $gallery = Gallery::factory()->create(['user_id' => 1]);
    $policy = new GalleryPolicy;

    expect($policy->forceDelete(galleryUser(), $gallery))->toBeTrue();
});
