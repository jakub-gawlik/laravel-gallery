<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Models;

use Jgawlik\LaravelGallery\Concerns\OwnedByUser;
use Jgawlik\LaravelGallery\Database\Factories\GalleryImageFactory;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $gallery_id
 * @property string $path
 * @property string $disk
 * @property string $title
 * @property string|null $description
 * @property string|null $alt_text
 * @property int $sort_order
 * @property-read Gallery $gallery
 *
 * @method static Builder<static> query()
 */
class GalleryImage extends Model
{
    /**
     * @use HasFactory<GalleryImageFactory>
     */
    use HasFactory, OwnedByUser;

    protected $fillable = [
        'gallery_id',
        'path',
        'disk',
        'title',
        'description',
        'alt_text',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::deleted(function (GalleryImage $image): void {
            if ($image->path) {
                Storage::disk($image->disk ?: 'public')->delete($image->path);
            }
        });
    }

    protected static function newFactory(): GalleryImageFactory
    {
        return GalleryImageFactory::new();
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
            /** @phpstan-ignore-next-line */
            $query->ownedBy($userId);
        });
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function resolveRouteBindingQuery($query, $value, $field = null): Builder
    {
        return parent::resolveRouteBindingQuery($query, $value, $field)
            /** @phpstan-ignore-next-line */
            ->ownedBy(request()->user() ?? auth()->user());
    }

    /**
     * @return BelongsTo<Gallery, $this>
     */
    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class);
    }
}
