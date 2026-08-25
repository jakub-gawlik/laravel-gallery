<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGalleryImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:20'],
            'images.*.file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'images.*.title' => ['nullable', 'string', 'max:255'],
            'images.*.description' => ['nullable', 'string', 'max:65535'],
        ];
    }
}
