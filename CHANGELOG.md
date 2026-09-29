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
