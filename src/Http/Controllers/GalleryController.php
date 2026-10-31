<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Jgawlik\LaravelGallery\Actions\StoreGalleryCover;
use Jgawlik\LaravelGallery\Http\Requests\GalleryIndexRequest;
use Jgawlik\LaravelGallery\Http\Requests\StoreGalleryRequest;
use Jgawlik\LaravelGallery\Http\Requests\UpdateGalleryRequest;
use Jgawlik\LaravelGallery\Http\Resources\GalleryResource;
use Jgawlik\LaravelGallery\Models\Gallery;

class GalleryController extends Controller
{
    public function index(GalleryIndexRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Gallery::class);

        $query = Gallery::query()
            ->ownedBy($request->user())
            ->with('images');

        if ($request->validatedSearch() !== null) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->validatedSearch());
            $query->where(function ($q) use ($search): void {
                $q->whereRaw("name like ? escape '\\'", ["%$search%"])
                    ->orWhereRaw("description like ? escape '\\'", ["%$search%"]);
            });
        }

        $query->orderBy($request->validatedSort(), $request->validatedDirection());

        return GalleryResource::collection($query->paginate($request->validatedPerPage()));
    }

    public function store(StoreGalleryRequest $request, StoreGalleryCover $storeCover): GalleryResource
    {
        Gate::authorize('create', Gallery::class);

        $data = $request->validated();
        $user = $request->user();

        abort_unless($user !== null, 403);

        $slug = $data['slug'] ?? $this->generateUniqueSlug(Str::slug($data['name']), $user->getAuthIdentifier());

        $gallery = DB::transaction(function () use ($request, $storeCover, $data, $user, $slug): Gallery {
            $gallery = Gallery::create([
                'user_id' => $user->getAuthIdentifier(),
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
            ]);

            $cover = $request->file('image');

            if ($cover instanceof UploadedFile) {
                $storeCover($gallery, $cover);
            }

            return $gallery;
        });

        return new GalleryResource($gallery->load('images'));
    }

    public function show(Gallery $gallery): GalleryResource
    {
        Gate::authorize('view', $gallery);

        return new GalleryResource($gallery->load('images'));
    }

    public function update(UpdateGalleryRequest $request, Gallery $gallery, StoreGalleryCover $storeCover): GalleryResource
    {
        Gate::authorize('update', $gallery);

        $data = $request->validated();

        if (! array_key_exists('slug', $data) || blank($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug(Str::slug($data['name']), $gallery->user_id, $gallery->id);
        }

        unset($data['image']);

        $gallery->update($data);

        $cover = $request->file('image');

        if ($cover instanceof UploadedFile) {
            $storeCover($gallery, $cover);
        }

        $gallery->refresh()->load('images');

        return new GalleryResource($gallery);
    }

    public function destroy(Gallery $gallery): Response
    {
        Gate::authorize('delete', $gallery);

        $gallery->delete();

        return response()->noContent();
    }

    private function generateUniqueSlug(string $slug, int|string $userId, ?int $excludeId = null): string
    {
        $baseSlug = $slug;
        $suffix = 1;

        $query = Gallery::where('user_id', $userId)->where('slug', $slug);
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $query = Gallery::where('user_id', $userId)->where('slug', $slug);
            if ($excludeId !== null) {
                $query->where('id', '!=', $excludeId);
            }
            $suffix++;
        }

        return $slug;
    }
}
