<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Jgawlik\LaravelGallery\Models\GalleryImage;

class UpdateGalleryImageRequest extends FormRequest
{
    public function authorize(): Response
    {
        $image = $this->route('image');

        return $image instanceof GalleryImage
            ? Gate::inspect('update', $image)
            : Response::deny();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'alt_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
