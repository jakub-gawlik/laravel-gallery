<?php

declare(strict_types=1);

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Image\Image;
use Illuminate\Image\ImageManager;
use Illuminate\Support\Facades\Queue;
use Jgawlik\LaravelGallery\Actions\GenerateThumbnail;
use Jgawlik\LaravelGallery\Contracts\Thumbnailable;
use Jgawlik\LaravelGallery\Jobs\GenerateGalleryCoverThumbnail;
use Jgawlik\LaravelGallery\Models\Gallery;
use Jgawlik\LaravelGallery\Tests\TestCase;

it('is queued and discards itself when the gallery is gone', function () {
    /** @var TestCase $this */
    Queue::fake();

    $gallery = Gallery::factory()->withImage()->create();
    $job = new GenerateGalleryCoverThumbnail($gallery);

    expect($job)->toBeInstanceOf(ShouldQueue::class)
        ->and($job->deleteWhenMissingModels)->toBeTrue();

    GenerateGalleryCoverThumbnail::dispatch($gallery);

    Queue::assertPushed(GenerateGalleryCoverThumbnail::class, fn (GenerateGalleryCoverThumbnail $job): bool => $job->gallery->is($gallery));
});

it('delegates to the generate thumbnail action', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->withImage()->create();
    $generator = new class(app(ImageManager::class)) extends GenerateThumbnail
    {
        public ?Thumbnailable $generatedFor = null;

        public function __invoke(Thumbnailable $target, ?Image $source = null): Thumbnailable
        {
            $this->generatedFor = $target;

            return $target;
        }
    };

    (new GenerateGalleryCoverThumbnail($gallery))->handle($generator);

    expect($generator->generatedFor)->toBe($gallery);
});

it('does nothing when the gallery no longer has a cover', function () {
    /** @var TestCase $this */
    $gallery = Gallery::factory()->create(['image_path' => null]);
    $generator = new class(app(ImageManager::class)) extends GenerateThumbnail
    {
        public ?Thumbnailable $generatedFor = null;

        public function __invoke(Thumbnailable $target, ?Image $source = null): Thumbnailable
        {
            $this->generatedFor = $target;

            return $target;
        }
    };

    (new GenerateGalleryCoverThumbnail($gallery))->handle($generator);

    expect($generator->generatedFor)->toBeNull();
});
