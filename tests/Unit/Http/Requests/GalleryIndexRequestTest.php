<?php

declare(strict_types=1);

use Jgawlik\LaravelGallery\Http\Requests\GalleryIndexRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\In;

/**
 * @param  array<string, mixed>  $data
 */
function createValidatedRequest(array $data = []): GalleryIndexRequest
{
    $request = new GalleryIndexRequest($data);
    $request->setValidator(Validator::make($data, $request->rules()));

    return $request;
}

it('is always authorized', function () {
    $request = new GalleryIndexRequest;

    expect($request->authorize())->toBeTrue();
});

it('allows search, sort, direction, and per_page parameters', function () {
    $request = new GalleryIndexRequest;

    $rules = $request->rules();

    expect($rules['search'])->toContain('nullable', 'string', 'max:255')
        ->and($rules['sort'])->toContain('nullable', 'string')
        ->and($rules['sort'][2])->toBeInstanceOf(In::class)
        ->and($rules['direction'])->toContain('nullable', 'string')
        ->and($rules['direction'][2])->toBeInstanceOf(In::class)
        ->and($rules['per_page'])->toContain('nullable', 'integer', 'min:1', 'max:100');
});

it('returns default values', function () {
    $request = createValidatedRequest();

    expect($request->validatedSearch())->toBeNull()
        ->and($request->validatedSort())->toBe('created_at')
        ->and($request->validatedDirection())->toBe('desc')
        ->and($request->validatedPerPage())->toBe(15);
});

it('returns validated values', function () {
    $request = createValidatedRequest([
        'search' => 'summer',
        'sort' => 'name',
        'direction' => 'asc',
        'per_page' => 5,
    ]);

    expect($request->validatedSearch())->toBe('summer')
        ->and($request->validatedSort())->toBe('name')
        ->and($request->validatedDirection())->toBe('asc')
        ->and($request->validatedPerPage())->toBe(5);
});
