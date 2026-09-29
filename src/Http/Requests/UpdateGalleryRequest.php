<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Jgawlik\LaravelGallery\Models\Gallery;

class UpdateGalleryRequest extends FormRequest
{
    public function authorize(): Response
    {
        $gallery = $this->route('gallery');

        return $gallery instanceof Gallery
            ? Gate::inspect('update', $gallery)
            : Response::deny();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $galleryId = $this->route('gallery');

        if ($galleryId instanceof Model) {
            $galleryId = $galleryId->getKey();
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('galleries', 'slug')
                    ->ignore($galleryId)
                    ->where('user_id', $this->user()?->getAuthIdentifier()),
            ],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
