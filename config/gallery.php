<?php

declare(strict_types=1);

return [
    'disk' => (string) env('GALLERY_DISK', 'public'),
    'path' => (string) env('GALLERY_PATH', 'galleries'),
    'middleware' => ['api', 'auth'],

    /*
    |--------------------------------------------------------------------------
    | Thumbnails
    |--------------------------------------------------------------------------
    |
    | When enabled, a thumbnail is generated for every uploaded gallery image
    | using Laravel's Image facade (requires intervention/image). Thumbnails
    | are cover-cropped to the given dimensions, encoded in the given format
    | and stored next to the originals under the "path" sub-directory.
    |
    | Set "queue" to true to generate thumbnails in a queued job instead of
    | inline during the upload request.
    |
    */
    'thumbnails' => [
        'enabled' => (bool) env('GALLERY_THUMBNAILS', true),
        'width' => (int) env('GALLERY_THUMBNAIL_WIDTH', 800),
        'height' => (int) env('GALLERY_THUMBNAIL_HEIGHT', 450),
        'format' => (string) env('GALLERY_THUMBNAIL_FORMAT', 'webp'),
        'quality' => (int) env('GALLERY_THUMBNAIL_QUALITY', 75),
        'path' => (string) env('GALLERY_THUMBNAIL_PATH', 'thumbs'),
        'queue' => (bool) env('GALLERY_THUMBNAILS_QUEUE', false),
    ],
];
