<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGalleryRequest extends FormRequest
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
