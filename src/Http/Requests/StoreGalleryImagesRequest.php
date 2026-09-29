<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Jgawlik\LaravelGallery\Models\Gallery;
use Jgawlik\LaravelGallery\Models\GalleryImage;

class StoreGalleryImagesRequest extends FormRequest
{
    public function authorize(): Response
    {
        $gallery = $this->route('gallery');

        return $gallery instanceof Gallery
            ? Gate::inspect('create', [GalleryImage::class, $gallery])
            : Response::deny();
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
