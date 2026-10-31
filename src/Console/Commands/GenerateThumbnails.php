<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Jgawlik\LaravelGallery\Actions\GenerateThumbnail;
use Jgawlik\LaravelGallery\Contracts\Thumbnailable;
use Jgawlik\LaravelGallery\Jobs\GenerateGalleryCoverThumbnail;
use Jgawlik\LaravelGallery\Jobs\GenerateGalleryThumbnail;
use Jgawlik\LaravelGallery\Models\Gallery;
use Jgawlik\LaravelGallery\Models\GalleryImage;
use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;

class GenerateThumbnails extends Command
{
    protected $signature = 'gallery:thumbnails
        {--force : Regenerate thumbnails for images that already have one}
        {--queue : Dispatch a queued job per image instead of generating inline}';

    protected $description = 'Generate thumbnails for gallery images and gallery covers that are missing one';

    private int $failed = 0;

    public function handle(GenerateThumbnail $generateThumbnail): int
    {
        $force = (bool) $this->option('force');
        $queue = (bool) $this->option('queue');

        $images = GalleryImage::query()
            ->unless($force, fn (Builder $query) => $query->whereNull('thumbnail_path'));

        $covers = Gallery::query()
            ->whereNotNull('image_path')
            ->unless($force, fn (Builder $query) => $query->whereNull('image_thumbnail_path'));

        $total = (clone $images)->count() + (clone $covers)->count();

        if ($total === 0) {
            $this->components->info('All gallery images and covers already have thumbnails.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $this->process($images, $bar, fn (GalleryImage $image) => $queue
            ? GenerateGalleryThumbnail::dispatch($image)
            : $generateThumbnail($image), fn (GalleryImage $image): string => "Image #{$image->id} ({$image->path})");

        $this->process($covers, $bar, fn (Gallery $gallery) => $queue
            ? GenerateGalleryCoverThumbnail::dispatch($gallery)
            : $generateThumbnail($gallery), fn (Gallery $gallery): string => "Gallery #{$gallery->id} cover ({$gallery->image_path})");

        $bar->finish();
        $this->newLine(2);

        if ($queue) {
            $this->components->info("Dispatched {$total} thumbnail job(s).");

            return self::SUCCESS;
        }

        $generated = $total - $this->failed;
        $this->components->info("Generated {$generated} thumbnail(s), {$this->failed} failed.");

        return $this->failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @template TModel of Model&Thumbnailable
     *
     * @param  Builder<TModel>  $query
     * @param  callable(TModel): mixed  $handle
     * @param  callable(TModel): string  $describe
     */
    private function process(Builder $query, ProgressBar $bar, callable $handle, callable $describe): void
    {
        $query->chunkById(100, function (Collection $models) use ($bar, $handle, $describe): void {
            foreach ($models as $model) {
                try {
                    $handle($model);
                } catch (Throwable $e) {
                    $this->failed++;
                    $this->newLine();
                    $this->components->error("{$describe($model)}: {$e->getMessage()}");
                }

                $bar->advance();
            }
        });
    }
}
