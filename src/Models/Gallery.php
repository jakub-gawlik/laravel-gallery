<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Models;

use Jgawlik\LaravelGallery\Concerns\OwnedByUser;
use Jgawlik\LaravelGallery\Database\Factories\GalleryFactory;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $image_path
 * @property-read Collection<int, GalleryImage> $images
 *
 * @method static Builder<static> query()
 * @method static where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static Gallery create(array<string, mixed> $attributes = [])
 */
class Gallery extends Model
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
    ];

    protected static function booted(): void
    {
        static::deleting(function (Gallery $gallery): void {
            $gallery->images()->cursor()->each->delete();

            if ($gallery->image_path) {
                Storage::disk((string) config('gallery.disk', 'public'))
                    ->delete($gallery->image_path);
            }
        });
    }

    protected static function newFactory(): GalleryFactory
    {
        return GalleryFactory::new();
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

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function resolveRouteBindingQuery($query, $value, $field = null): Builder
    {
        /** @phpstan-ignore-next-line */
        return parent::resolveRouteBindingQuery($query, $value, $field)->ownedBy(request()->user() ?? auth()->user());
    }

    /**
     * @return HasMany<GalleryImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(GalleryImage::class)->orderBy('sort_order');
    }
}
