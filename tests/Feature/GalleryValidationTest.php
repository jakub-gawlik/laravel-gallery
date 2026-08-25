<?php

declare(strict_types=1);

use Jgawlik\LaravelGallery\Models\Gallery;
use Jgawlik\LaravelGallery\Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->actingAs(galleryUser());
});

it('requires a name to create a gallery', function () {
    /** @var TestCase $this */
    $this->postJson('/api/v1/galleries', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('requires a valid image for a gallery', function () {
    /** @var TestCase $this */
    $this->postJson('/api/v1/galleries', [
        'name' => 'Test Gallery',
        'image' => 'not-an-image',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);
});

it('enforces unique slugs when creating a gallery', function () {
    /** @var TestCase $this */
    Gallery::factory()->create(['slug' => 'taken-slug']);

    $this->postJson('/api/v1/galleries', [
        'name' => 'Test Gallery',
        'slug' => 'taken-slug',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slug']);
});

it('respects a custom slug', function () {
    /** @var TestCase $this */
    $this->postJson('/api/v1/galleries', [
        'name' => 'Test Gallery',
        'slug' => 'my-custom-slug',
    ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'my-custom-slug');
});

it('requires a name to update a gallery', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->create();

    $this->putJson("/api/v1/galleries/{$gallery->id}", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('enforces unique slugs when updating a gallery', function () {
    /** @var TestCase $this */
    $existing = Gallery::factory()->create(['slug' => 'taken-slug']);
    $gallery = Gallery::factory()->create();

    $this->putJson("/api/v1/galleries/{$gallery->id}", [
        'name' => 'Updated Gallery',
        'slug' => 'taken-slug',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slug']);
});

it('allows keeping the same slug when updating', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->create(['slug' => 'same-slug']);

    $this->putJson("/api/v1/galleries/{$gallery->id}", [
        'name' => 'Updated Gallery',
        'slug' => 'same-slug',
    ])
        ->assertOk()
        ->assertJsonPath('data.slug', 'same-slug');
});

it('regenerates the slug when blank on update', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->create(['slug' => 'old-slug']);

    $this->putJson("/api/v1/galleries/{$gallery->id}", [
        'name' => 'Brand New Name',
        'slug' => '',
    ])
        ->assertOk()
        ->assertJsonPath('data.slug', 'brand-new-name');
});

it('returns a 404 for a missing gallery', function () {
    /** @var TestCase $this */
    $this->getJson('/api/v1/galleries/99999')
        ->assertNotFound();
});

it('can update a gallery using patch', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->create(['name' => 'Old Name']);

    $this->patchJson("/api/v1/galleries/{$gallery->id}", [
        'name' => 'Patched Name',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Patched Name');
});

it('handles slug collisions with suffixes when auto-generating', function () {
    /** @var TestCase $this */
    Gallery::factory()->create(['name' => 'Summer Photos', 'slug' => 'summer-photos']);
    Gallery::factory()->create(['name' => 'Summer Photos', 'slug' => 'summer-photos-1']);

    $this->postJson('/api/v1/galleries', [
        'name' => 'Summer Photos',
    ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'summer-photos-2');
});

it('handles slug collisions during update with suffix exclusion', function () {
    /** @var TestCase $this */
    $this->actingAs(galleryUser());

    // Create galleries with conflicting slugs
    Gallery::factory()->create(['name' => 'Old Gallery', 'slug' => 'new-name']);
    Gallery::factory()->create(['name' => 'Another', 'slug' => 'new-name-1']);
    $galleryToUpdate = Gallery::factory()->create(['name' => 'Gallery to Update', 'slug' => 'old-slug']);

    // Update the gallery to "New Name", which should get "new-name-2" (excluding self and the -1)
    $this->putJson("/api/v1/galleries/{$galleryToUpdate->id}", [
        'name' => 'New Name',
    ])
        ->assertOk()
        ->assertJsonPath('data.slug', 'new-name-2');
});
