<?php

declare(strict_types=1);

use Jgawlik\LaravelGallery\Models\Gallery;
use Jgawlik\LaravelGallery\Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->actingAs(galleryUser());
});

it('paginates gallery results', function () {
    /** @var TestCase $this */
    Gallery::factory()->count(25)->create();

    $this->getJson('/api/v1/galleries?per_page=10')
        ->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonPath('meta.last_page', 3)
        ->assertJsonPath('meta.total', 25)
        ->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);
});

it('searches galleries by name', function () {
    /** @var TestCase $this */
    Gallery::factory()->create(['name' => 'Summer Vacation']);
    Gallery::factory()->create(['name' => 'Winter Trip']);
    Gallery::factory()->create(['name' => 'Random Photos']);

    $this->getJson('/api/v1/galleries?search=summer')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Summer Vacation');
});

it('searches galleries by description', function () {
    /** @var TestCase $this */
    Gallery::factory()->create([
        'name' => 'Gallery One',
        'description' => 'Photos from the beach',
    ]);
    Gallery::factory()->create([
        'name' => 'Gallery Two',
        'description' => 'Mountain hiking trip',
    ]);

    $this->getJson('/api/v1/galleries?search=beach')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Gallery One');
});

it('sorts galleries by name ascending', function () {
    /** @var TestCase $this */
    Gallery::factory()->create(['name' => 'Zebra Gallery']);
    Gallery::factory()->create(['name' => 'Alpha Gallery']);
    Gallery::factory()->create(['name' => 'Beta Gallery']);

    $this->getJson('/api/v1/galleries?sort=name&direction=asc')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Alpha Gallery')
        ->assertJsonPath('data.1.name', 'Beta Gallery')
        ->assertJsonPath('data.2.name', 'Zebra Gallery');
});

it('sorts galleries by name descending', function () {
    /** @var TestCase $this */
    Gallery::factory()->create(['name' => 'Alpha Gallery']);
    Gallery::factory()->create(['name' => 'Zebra Gallery']);

    $this->getJson('/api/v1/galleries?sort=name&direction=desc')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Zebra Gallery')
        ->assertJsonPath('data.1.name', 'Alpha Gallery');
});

it('defaults to sorting by created_at descending', function () {
    /** @var TestCase $this */
    $first = Gallery::factory()->create([
        'name' => 'First',
        'created_at' => now()->subDay(),
    ]);
    $second = Gallery::factory()->create([
        'name' => 'Second',
        'created_at' => now(),
    ]);

    $this->getJson('/api/v1/galleries')
        ->assertOk()
        ->assertJsonPath('data.0.id', $second->id)
        ->assertJsonPath('data.1.id', $first->id);
});

it('rejects invalid sort fields', function () {
    /** @var TestCase $this */
    $this->getJson('/api/v1/galleries?sort=invalid')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['sort']);
});

it('rejects invalid sort directions', function () {
    /** @var TestCase $this */
    $this->getJson('/api/v1/galleries?direction=invalid')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['direction']);
});

it('rejects per_page below one', function () {
    /** @var TestCase $this */
    $this->getJson('/api/v1/galleries?per_page=0')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['per_page']);
});

it('rejects per_page above one hundred', function () {
    /** @var TestCase $this */
    $this->getJson('/api/v1/galleries?per_page=101')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['per_page']);
});
