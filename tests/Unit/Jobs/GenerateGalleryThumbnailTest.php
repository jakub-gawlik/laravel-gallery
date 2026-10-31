<?php

declare(strict_types=1);

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Image\Image;
use Illuminate\Image\ImageManager;
use Illuminate\Support\Facades\Queue;
use Jgawlik\LaravelGallery\Actions\GenerateThumbnail;
use Jgawlik\LaravelGallery\Jobs\GenerateGalleryThumbnail;
use Jgawlik\LaravelGallery\Models\GalleryImage;
use Jgawlik\LaravelGallery\Tests\TestCase;

it('is queued and discards itself when the image is gone', function () {
    /** @var TestCase $this */
    Queue::fake();

    $image = GalleryImage::factory()->create();
    $job = new GenerateGalleryThumbnail($image);

    expect($job)->toBeInstanceOf(ShouldQueue::class)
        ->and($job->deleteWhenMissingModels)->toBeTrue();

    GenerateGalleryThumbnail::dispatch($image);

    Queue::assertPushed(GenerateGalleryThumbnail::class, fn (GenerateGalleryThumbnail $job): bool => $job->image->is($image));
});

it('delegates to the generate thumbnail action', function () {
    /** @var TestCase $this */
    $image = GalleryImage::factory()->create();

    $generator = new class(app(ImageManager::class)) extends GenerateThumbnail
    {
        public ?GalleryImage $generatedFor = null;

        public function __invoke(GalleryImage $image, ?Image $source = null): GalleryImage
        {
            $this->generatedFor = $image;

            return $image;
        }
    };

    (new GenerateGalleryThumbnail($image))->handle($generator);

    expect($generator->generatedFor?->is($image))->toBeTrue();
});
