<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Jgawlik\LaravelGallery\Models\Gallery;

/**
 * @property-read Gallery $resource
 */
class GalleryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $hasThumbnail = $this->resource->hasImageThumbnail();

        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'description' => $this->resource->description,
            'image_url' => $this->resource->imageUrl(),
            'image_width' => $this->resource->image_width,
            'image_height' => $this->resource->image_height,
            'image_thumbnail_url' => $this->resource->imageThumbnailUrl(),
            'image_thumbnail_width' => $hasThumbnail ? $this->resource->image_thumbnail_width : $this->resource->image_width,
            'image_thumbnail_height' => $hasThumbnail ? $this->resource->image_thumbnail_height : $this->resource->image_height,
            'images' => $this->whenLoaded('images', fn () => GalleryImageResource::collection($this->resource->images)),
        ];
    }
}
