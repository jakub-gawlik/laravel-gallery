<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Jgawlik\LaravelGallery\Concerns\OwnedByUser;
use Jgawlik\LaravelGallery\Contracts\Ownable;
use Jgawlik\LaravelGallery\Contracts\Thumbnailable;
use Jgawlik\LaravelGallery\Database\Factories\GalleryImageFactory;

/**
 * @property int $id
 * @property int $gallery_id
 * @property string $path
 * @property string|null $thumbnail_path
 * @property string|null $disk
 * @property int|null $width
 * @property int|null $height
 * @property int|null $thumbnail_width
 * @property int|null $thumbnail_height
 * @property string $title
 * @property string|null $description
 * @property string|null $alt_text
 * @property int $sort_order
 * @property-read Gallery $gallery
 *
 * @method static Builder<static> query()
 */
class GalleryImage extends Model implements Ownable, Thumbnailable
{
    /**
     * @use HasFactory<GalleryImageFactory>
     */
    use HasFactory, OwnedByUser;

    protected $fillable = [
        'gallery_id',
        'path',
        'thumbnail_path',
        'disk',
        'width',
        'height',
        'thumbnail_width',
        'thumbnail_height',
        'title',
        'description',
        'alt_text',
        'sort_order',
    ];

    protected $casts = [
        'width' => 'integer',
        'height' => 'integer',
        'thumbnail_width' => 'integer',
        'thumbnail_height' => 'integer',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::deleted(function (GalleryImage $image): void {
            $image->deleteFiles();
        });
    }

    protected static function newFactory(): GalleryImageFactory
    {
        return GalleryImageFactory::new();
    }

    /**
     * The name of the storage disk holding this image's files.
     */
    public function diskName(): string
    {
        return $this->disk ?: 'public';
    }

    /**
     * The public URL of the original image.
     */
    public function url(): string
    {
        return Storage::disk($this->diskName())->url($this->path);
    }

    public function hasThumbnail(): bool
    {
        return $this->thumbnail_path !== null;
    }

    /**
     * The public URL of the thumbnail, falling back to the original image.
     */
    public function thumbnailUrl(): string
    {
        return $this->thumbnail_path !== null
            ? Storage::disk($this->diskName())->url($this->thumbnail_path)
            : $this->url();
    }

    /**
     * Delete the original image and its thumbnail from storage.
     */
    public function deleteFiles(): void
    {
        $disk = Storage::disk($this->diskName());

        if ($this->path) {
            $disk->delete($this->path);
        }

        if ($this->thumbnail_path) {
            $disk->delete($this->thumbnail_path);
        }
    }

    public function sourceImagePath(): ?string
    {
        return $this->path;
    }

    public function currentThumbnailPath(): ?string
    {
        return $this->thumbnail_path;
    }

    public function recordThumbnail(string $path, int $width, int $height): void
    {
        $this->forceFill([
            'thumbnail_path' => $path,
            'thumbnail_width' => $width,
            'thumbnail_height' => $height,
        ])->save();
    }

    /**
     *  Scope a query to only include galleries owned by the given user.
     *  This resolves the ownedBy() method in the Gallery model, i.e.
     *  Gallery::query()->ownedBy($request->user())
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOwnedBy(Builder $query, Authenticatable|int|string|null $user): Builder
    {
        $userId = $this->extractUserId($user);

        if ($userId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('gallery', function (Builder $query) use ($userId): void {
            $query->ownedBy($userId);
        });
    }

    public function isOwnedBy(Authenticatable $user): bool
    {
        return $this->gallery->isOwnedBy($user);
    }

    /**
     * @return BelongsTo<Gallery, $this>
     */
    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class);
    }
}
