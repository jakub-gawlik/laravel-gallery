# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- **Breaking:** Route model binding for `Gallery` and `GalleryImage` is no longer scoped to the authenticated user. Ownership is now enforced through policies (`OwnedModelPolicy`), which respond with a 404 for records owned by other users. Consumers can register their own policies via `Gate::policy()` to allow guest/public access to galleries.
- `GalleryPolicy` and `GalleryImagePolicy` now extend the new abstract `OwnedModelPolicy` and return `Illuminate\Auth\Access\Response` (with `denyAsNotFound()`) instead of booleans for owner-scoped abilities.
- `UpdateGalleryRequest` and `StoreGalleryImagesRequest` now authorize through the gate in `authorize()`, so unauthorized requests are rejected (as 404) before validation runs.

### Added

- `Ownable` contract implemented by `Gallery` and `GalleryImage` (`isOwnedBy()`).
- Thumbnail generation for gallery images via Laravel's `Image` facade, configured under `gallery.thumbnails` (dimensions, format, quality, sub-directory, queueing, on/off switch).
- Migration adding `thumbnail_path`, `width`, `height`, `thumbnail_width` and `thumbnail_height` to `gallery_images`.
- `StoreGalleryImage` action encapsulating the upload flow (store original, detect dimensions, generate thumbnail) so host apps can reuse it outside the package controller.
- `GenerateThumbnail` action and `GenerateGalleryThumbnail` queued job.
- `gallery:thumbnails` artisan command to backfill (`--force` to regenerate, `--queue` to dispatch jobs).
- `GalleryImageResource` now exposes `width`, `height`, `thumbnail_url`, `thumbnail_width` and `thumbnail_height`; the `thumbnail_*` fields fall back to the original when no thumbnail exists.
- `GalleryImage::url()`, `thumbnailUrl()`, `hasThumbnail()`, `diskName()` and `deleteFiles()` helpers; deleting an image now removes its thumbnail too.
- `GalleryImageFactory::withThumbnail()` state.
- New dependency on `intervention/image` (^4.0), required by Laravel's GD/Imagick image drivers.

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
