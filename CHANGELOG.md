# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.4] - 2026-10-31

### Added

- Thumbnails for gallery cover images. The `galleries` table gains `image_thumbnail_path`, `image_width`, `image_height`, `image_thumbnail_width` and `image_thumbnail_height` (new migration), and `GalleryResource` exposes `image_width`, `image_height`, `image_thumbnail_url`, `image_thumbnail_width` and `image_thumbnail_height`, falling back to the cover when no thumbnail exists.
- `StoreGalleryCover` action that stores a gallery cover, records its dimensions, generates the thumbnail (inline or queued) and removes the previous cover and thumbnail. `GalleryController` now uses it for create and update.
- `GenerateGalleryCoverThumbnail` queued job.
- `Thumbnailable` contract implemented by `Gallery` and `GalleryImage`; `GenerateThumbnail` accepts any `Thumbnailable`.
- `Gallery::imageUrl()`, `imageThumbnailUrl()`, `hasImageThumbnail()`, `diskName()` and `deleteImageFiles()` helpers; deleting a gallery now removes its cover thumbnail too.
- `GalleryFactory::withImage()` and `withImageThumbnail()` states.
- Optional `$directory` argument on `StoreGalleryImage` and `StoreGalleryCover` to store uploads in a sub-directory of the configured `gallery.path` (for example one folder per gallery).
- Forgejo Actions workflow running Pint, PHPStan and the test suite.
- `composer.lock` is now committed, so CI and contributors install the same dependency set. It pins `symfony/mime` 8.1.10 ([CVE-2026-45070](https://symfony.com/cve-2026-45070)).

### Changed

- Thumbnails are now stored next to their source image (the source directory plus `thumbnails.path`) instead of always under `gallery.path`/`thumbnails.path`. Existing layouts are unaffected; `GenerateThumbnail::directory()` now takes the target as its argument.
- `gallery:thumbnails` also backfills gallery covers; its summary messages mention covers.
- Creating a gallery with a cover now runs in a database transaction.
- Changelog restructured: the 1.0.1, 1.0.2 and 1.0.3 releases were previously listed under Unreleased.

## [1.0.3] - 2026-10-31

### Added

- Thumbnail generation for gallery images via Laravel's `Image` facade, configured under `gallery.thumbnails` (dimensions, format, quality, sub-directory, queueing, on/off switch).
- Migration adding `thumbnail_path`, `width`, `height`, `thumbnail_width` and `thumbnail_height` to `gallery_images`.
- `StoreGalleryImage` action encapsulating the upload flow (store original, detect dimensions, generate thumbnail) so host apps can reuse it outside the package controller.
- `GenerateThumbnail` action and `GenerateGalleryThumbnail` queued job.
- `gallery:thumbnails` artisan command to backfill (`--force` to regenerate, `--queue` to dispatch jobs).
- `GalleryImageResource` now exposes `width`, `height`, `thumbnail_url`, `thumbnail_width` and `thumbnail_height`; the `thumbnail_*` fields fall back to the original when no thumbnail exists.
- `GalleryImage::url()`, `thumbnailUrl()`, `hasThumbnail()`, `diskName()` and `deleteFiles()` helpers; deleting an image now removes its thumbnail too.
- `GalleryImageFactory::withThumbnail()` state.
- New dependency on `intervention/image` (^4.0), required by Laravel's GD/Imagick image drivers.

## [1.0.2] - 2026-09-30

### Added

- `PUT/PATCH /api/v1/galleries/{gallery}/images/{image}` endpoint to update a gallery image's `title`, `description`, `alt_text` and `sort_order`, with `UpdateGalleryImageRequest` authorizing through the gate.

## [1.0.1] - 2026-09-29

### Changed

- **Breaking:** Route model binding for `Gallery` and `GalleryImage` is no longer scoped to the authenticated user. Ownership is now enforced through policies (`OwnedModelPolicy`), which respond with a 404 for records owned by other users. Consumers can register their own policies via `Gate::policy()` to allow guest/public access to galleries. This shipped in a patch release; if you relied on the scoped binding, register your own policies before upgrading.
- `GalleryPolicy` and `GalleryImagePolicy` now extend the new abstract `OwnedModelPolicy` and return `Illuminate\Auth\Access\Response` (with `denyAsNotFound()`) instead of booleans for owner-scoped abilities.
- `UpdateGalleryRequest` and `StoreGalleryImagesRequest` now authorize through the gate in `authorize()`, so unauthorized requests are rejected (as 404) before validation runs.

### Added

- `Ownable` contract implemented by `Gallery` and `GalleryImage` (`isOwnedBy()`).

## [1.0.0] - 2026-07-28

### Added

- Initial release of `jgawlik/laravel-gallery`.
- Gallery CRUD API endpoints.
- Gallery image upload and deletion endpoints.
- Configurable storage disk and path.
- Database migrations and model factories.
- Pest test suite with full coverage target.
- PHPStan level 7 static analysis.
- Laravel Pint code style enforcement.

[Unreleased]: https://github.com/jgawlik/laravel-gallery/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/jgawlik/laravel-gallery/releases/tag/v1.0.0
