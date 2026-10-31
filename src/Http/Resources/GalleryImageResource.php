<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Jgawlik\LaravelGallery\Models\GalleryImage;

/**
 * @property-read GalleryImage $resource
 */
class GalleryImageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $hasThumbnail = $this->resource->hasThumbnail();

        return [
            'id' => $this->resource->id,
            'path' => $this->resource->path,
            'url' => $this->resource->url(),
            'width' => $this->resource->width,
            'height' => $this->resource->height,
            'thumbnail_url' => $this->resource->thumbnailUrl(),
            'thumbnail_width' => $hasThumbnail ? $this->resource->thumbnail_width : $this->resource->width,
            'thumbnail_height' => $hasThumbnail ? $this->resource->thumbnail_height : $this->resource->height,
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'alt_text' => $this->resource->alt_text,
            'sort_order' => $this->resource->sort_order,
        ];
    }
}
