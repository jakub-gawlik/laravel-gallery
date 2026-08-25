<?php

declare(strict_types=1);

return [
    'disk' => (string) env('GALLERY_DISK', 'public'),
    'path' => (string) env('GALLERY_PATH', 'galleries'),
    'middleware' => ['api', 'auth'],
];
