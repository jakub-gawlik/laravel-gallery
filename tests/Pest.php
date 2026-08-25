<?php

declare(strict_types=1);

use Jgawlik\LaravelGallery\Tests\TestCase;
use Illuminate\Auth\GenericUser;

uses(TestCase::class)->in(__DIR__);

function galleryUser(int $id = 1): GenericUser
{
    return new GenericUser([
        'id' => $id,
        'name' => 'Test User',
    ]);
}
