<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Jgawlik\LaravelGallery\Http\Requests\StoreGalleryImagesRequest;
use Jgawlik\LaravelGallery\Http\Requests\UpdateGalleryImageRequest;
use Jgawlik\LaravelGallery\Http\Resources\GalleryImageResource;
use Jgawlik\LaravelGallery\Models\Gallery;
use Jgawlik\LaravelGallery\Models\GalleryImage;
use RuntimeException;

class GalleryImageController extends Controller
{
    /**
     * @throws \Throwable
     */
    public function store(StoreGalleryImagesRequest $request, Gallery $gallery): JsonResponse
    {
        Gate::authorize('create', [GalleryImage::class, $gallery]);

        $storedImages = [];
        $writtenFiles = [];
        $disk = (string) config('gallery.disk', 'public');
        $path = (string) config('gallery.path', 'galleries');

        try {
            DB::transaction(function () use (
                $request,
                $gallery,
                $disk,
                $path,
                &$storedImages,
                &$writtenFiles
            ): void {
                $startOrder = (int) $gallery->images()
                    ->lockForUpdate()
                    ->max('sort_order');

                foreach ($request->input('images') as $index => $image) {
                    $file = $request->file("images.{$index}.file");

                    if (! $file instanceof UploadedFile) {
                        throw new RuntimeException('Failed to store gallery image.');
                    }

                    $filePath = $file->store($path, $disk);

                    if ($filePath === false) {
                        throw new RuntimeException('Failed to store gallery image.');
                    }

                    $writtenFiles[] = $filePath;

                    $storedImages[] = $gallery->images()->create([
                        'path' => $filePath,
                        'disk' => $disk,
                        'title' => $image['title'] ?? null,
                        'description' => $image['description'] ?? null,
                        'sort_order' => $startOrder + $index + 1,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            foreach ($writtenFiles as $filePath) {
                Storage::disk($disk)->delete($filePath);
            }
            throw $e;
        }

        return GalleryImageResource::collection(collect($storedImages))
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateGalleryImageRequest $request,
        Gallery $gallery,
        GalleryImage $image
    ): GalleryImageResource {
        abort_unless($image->gallery_id === $gallery->id, 404);

        Gate::authorize('update', $image);

        $image->update($request->validated());

        return new GalleryImageResource($image);
    }

    public function destroy(Gallery $gallery, GalleryImage $image): Response
    {
        abort_unless($image->gallery_id === $gallery->id, 404);

        Gate::authorize('delete', $image);

        $image->delete();

        return response()->noContent();
    }
}
