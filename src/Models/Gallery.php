<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Jgawlik\LaravelGallery\Concerns\OwnedByUser;
use Jgawlik\LaravelGallery\Contracts\Ownable;
use Jgawlik\LaravelGallery\Contracts\Thumbnailable;
use Jgawlik\LaravelGallery\Database\Factories\GalleryFactory;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $image_path
 * @property string|null $image_thumbnail_path
 * @property int|null $image_width
 * @property int|null $image_height
 * @property int|null $image_thumbnail_width
 * @property int|null $image_thumbnail_height
 * @property-read Collection<int, GalleryImage> $images
 *
 * @method static Builder<static> query()
 * @method static where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static Gallery create(array<string, mixed> $attributes = [])
 */
class Gallery extends Model implements Ownable, Thumbnailable
{
    /**
     * @use HasFactory<GalleryFactory>
     */
    use HasFactory;

    use OwnedByUser;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'image_path',
        'image_thumbnail_path',
        'image_width',
        'image_height',
        'image_thumbnail_width',
        'image_thumbnail_height',
    ];

    protected $casts = [
        'image_width' => 'integer',
        'image_height' => 'integer',
        'image_thumbnail_width' => 'integer',
        'image_thumbnail_height' => 'integer',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Gallery $gallery): void {
            $gallery->images()->cursor()->each->delete();

            $gallery->deleteImageFiles();
        });
    }

    protected static function newFactory(): GalleryFactory
    {
        return GalleryFactory::new();
    }

    /**
     * The name of the storage disk holding the cover image and its thumbnail.
     */
    public function diskName(): string
    {
        return (string) config('gallery.disk', 'public');
    }

    /**
     * The public URL of the cover image, if any.
     */
    public function imageUrl(): ?string
    {
        return $this->image_path !== null
            ? Storage::disk($this->diskName())->url($this->image_path)
            : null;
    }

    public function hasImageThumbnail(): bool
    {
        return $this->image_thumbnail_path !== null;
    }

    /**
     * The public URL of the cover thumbnail, falling back to the cover image.
     */
    public function imageThumbnailUrl(): ?string
    {
        return $this->image_thumbnail_path !== null
            ? Storage::disk($this->diskName())->url($this->image_thumbnail_path)
            : $this->imageUrl();
    }

    /**
     * Delete the cover image and its thumbnail from storage.
     */
    public function deleteImageFiles(): void
    {
        $disk = Storage::disk($this->diskName());

        if ($this->image_path) {
            $disk->delete($this->image_path);
        }

        if ($this->image_thumbnail_path) {
            $disk->delete($this->image_thumbnail_path);
        }
    }

    public function sourceImagePath(): ?string
    {
        return $this->image_path;
    }

    public function currentThumbnailPath(): ?string
    {
        return $this->image_thumbnail_path;
    }

    public function recordThumbnail(string $path, int $width, int $height): void
    {
        $this->forceFill([
            'image_thumbnail_path' => $path,
            'image_thumbnail_width' => $width,
            'image_thumbnail_height' => $height,
        ])->save();
    }

    /**
     * Scope a query to only include galleries owned by the given user.
     * This resolves the ownedBy() method in the Gallery model, i.e.
     * Gallery::query()->ownedBy($request->user())
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

        return $query->where('user_id', $userId);
    }

    public function isOwnedBy(Authenticatable $user): bool
    {
        return $this->ownsModel($user, $this);
    }

    /**
     * @return HasMany<GalleryImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(GalleryImage::class)->orderBy('sort_order');
    }
}
