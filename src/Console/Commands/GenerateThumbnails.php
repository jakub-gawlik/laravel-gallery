<?php

declare(strict_types=1);

namespace Jgawlik\LaravelGallery\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Jgawlik\LaravelGallery\Actions\GenerateThumbnail;
use Jgawlik\LaravelGallery\Jobs\GenerateGalleryThumbnail;
use Jgawlik\LaravelGallery\Models\GalleryImage;
use Throwable;

class GenerateThumbnails extends Command
{
    protected $signature = 'gallery:thumbnails
        {--force : Regenerate thumbnails for images that already have one}
        {--queue : Dispatch a queued job per image instead of generating inline}';

    protected $description = 'Generate thumbnails for gallery images that are missing one';

    public function handle(GenerateThumbnail $generateThumbnail): int
    {
        $query = GalleryImage::query()
            ->unless($this->option('force'), fn ($query) => $query->whereNull('thumbnail_path'));

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->components->info('All gallery images already have thumbnails.');

            return self::SUCCESS;
        }

        $queue = (bool) $this->option('queue');
        $failed = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById(100, function (Collection $images) use ($generateThumbnail, $queue, $bar, &$failed): void {
            /** @var GalleryImage $image */
            foreach ($images as $image) {
                if ($queue) {
                    GenerateGalleryThumbnail::dispatch($image);
                } else {
                    try {
                        $generateThumbnail($image);
                    } catch (Throwable $e) {
                        $failed++;
                        $this->newLine();
                        $this->components->error("Image #{$image->id} ({$image->path}): {$e->getMessage()}");
                    }
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        if ($queue) {
            $this->components->info("Dispatched {$total} thumbnail job(s).");

            return self::SUCCESS;
        }

        $generated = $total - $failed;
        $this->components->info("Generated {$generated} thumbnail(s), {$failed} failed.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
