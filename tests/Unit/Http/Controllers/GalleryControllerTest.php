<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Jgawlik\LaravelGallery\Http\Controllers\GalleryController;
use Jgawlik\LaravelGallery\Tests\TestCase;

it('throws when storing a gallery image fails', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $file = new class(__FILE__, 'image.jpg') extends UploadedFile
    {
        /**
         * @param  array<string, mixed>  $options
         */
        public function store($path = '', $options = []): string|bool
        {
            return false;
        }
    };

    $controller = new GalleryController;

    $method = new ReflectionMethod($controller, 'storeImage');

    expect(fn () => $method->invoke($controller, $file))
        ->toThrow(RuntimeException::class, 'Failed to store gallery image.');
});
