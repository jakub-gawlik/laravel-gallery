<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class GalleryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'description' => $this->resource->description,
            'image_url' => $this->resource->image_path !== null
                ? Storage::disk((string) config('gallery.disk', 'public'))->url($this->resource->image_path)
                : null,
            'images' => $this->whenLoaded('images', fn () => GalleryImageResource::collection($this->resource->images)),
        ];
    }
}
