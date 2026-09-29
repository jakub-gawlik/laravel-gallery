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

it('denies viewing a gallery owned by another user as not found', function () {
    $gallery = Gallery::factory()->create(['user_id' => 2]);
    $policy = new GalleryPolicy;

    $response = $policy->view(galleryUser(), $gallery);

    expect($response->denied())->toBeTrue()
        ->and($response->status())->toBe(404);
});

it('allows viewing a gallery owned by the current user', function () {
    $gallery = Gallery::factory()->create(['user_id' => 1]);
    $policy = new GalleryPolicy;

    expect($policy->view(galleryUser(), $gallery)->allowed())->toBeTrue();
});

it('allows creating a gallery for an authenticated user', function () {
    $policy = new GalleryPolicy;

    expect($policy->create(galleryUser())->allowed())->toBeTrue();
});

it('denies creating a gallery for a guest', function () {
    $policy = new GalleryPolicy;

    expect($policy->create(null)->denied())->toBeTrue();
});

it('allows updating a gallery owned by the current user', function () {
    $gallery = Gallery::factory()->create(['user_id' => 1]);
    $policy = new GalleryPolicy;

    expect($policy->update(galleryUser(), $gallery)->allowed())->toBeTrue();
});

it('denies updating a gallery owned by another user as not found', function () {
    $gallery = Gallery::factory()->create(['user_id' => 2]);
    $policy = new GalleryPolicy;

    $response = $policy->update(galleryUser(), $gallery);

    expect($response->denied())->toBeTrue()
        ->and($response->status())->toBe(404);
});

it('denies resource actions for a guest', function () {
    $gallery = Gallery::factory()->create(['user_id' => 1]);
    $policy = new GalleryPolicy;

    expect($policy->view(null, $gallery)->denied())->toBeTrue()
        ->and($policy->update(null, $gallery)->denied())->toBeTrue()
        ->and($policy->delete(null, $gallery)->denied())->toBeTrue()
        ->and($policy->restore(null, $gallery)->denied())->toBeTrue()
        ->and($policy->forceDelete(null, $gallery)->denied())->toBeTrue();
});

it('allows deleting a gallery owned by the current user', function () {
    $gallery = Gallery::factory()->create(['user_id' => 1]);
    $policy = new GalleryPolicy;

    expect($policy->delete(galleryUser(), $gallery)->allowed())->toBeTrue();
});

it('allows restoring a gallery owned by the current user', function () {
    $gallery = Gallery::factory()->create(['user_id' => 1]);
    $policy = new GalleryPolicy;

    expect($policy->restore(galleryUser(), $gallery)->allowed())->toBeTrue();
});

it('allows force deleting a gallery owned by the current user', function () {
    $gallery = Gallery::factory()->create(['user_id' => 1]);
    $policy = new GalleryPolicy;

    expect($policy->forceDelete(galleryUser(), $gallery)->allowed())->toBeTrue();
});
