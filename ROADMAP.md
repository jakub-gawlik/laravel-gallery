# Laravel Gallery Roadmap

This document outlines the planned evolution of `jgawlik/laravel-gallery` from a backend API package into a full gallery solution with optional, framework-specific management UIs and reusable presentation styles.

## Guiding Principles

- **Opt-in UI**: Frontend pages and components are always optional. A consumer app can use only the API, only the backend, or drop in a complete management interface.
- **Framework parity**: Vue and React implementations share the same backend contracts and feature set.
- **Style presets, not lock-in**: Predefined gallery styles ship as standalone CSS/JS bundles that can be ignored, overridden, or extended.
- **Laravel-native**: Assets are published, routes are namespaced, and authorization hooks into the host app.

---

## Phase 1 — Backend Stabilization (prerequisite for all UI work)

*Goal: Harden the existing API so it can reliably support multiple frontends. This phase should be complete before any Vue/React pages or style presets are published.*

- [x] Add authorization gates/policies for `Gallery` and `GalleryImage`.
- [x] Introduce a `gallery.middleware` config key for route-level protection.
- [x] Add pagination, filtering, and search to `GET /api/v1/galleries`.
- [ ] Support image reordering via `PUT /api/v1/galleries/{gallery}/images/reorder`.
- [ ] Add bulk image deletion.
- [ ] Add `alt_text` and `caption` handling to image uploads.
- [ ] Generate responsive image variants (thumbnail, webp) behind a config flag.
- [ ] Add URL signing helpers for private disks.
- [ ] Maintain Pest coverage at 100% and PHPStan level 7+ as new features land.

---

## Phase 2 — Admin UI Foundation

*Goal: Build the shared infrastructure that both Vue and React UIs will consume.*

- [ ] Add a `GalleryManager` contract and config-driven route registration.
- [ ] Create a namespaced web route group (e.g., `/gallery-manager`) with configurable prefix and middleware.
- [ ] Add a `GalleryStyle` model/config for storing and registering style presets.
- [ ] Provide a JSON manifest endpoint (`/gallery-manager/styles`) listing available styles.
- [ ] Add publishable view stubs so host apps can override layouts.
- [ ] Introduce a `gallery.php` lang file for copy and labels.

---

## Phase 3 — Vue Management Pages

*Goal: Ship a complete, optional Vue-based gallery manager.*

- [ ] Add a `resources/js/gallery-vue` build target using Vite.
- [ ] Create pages:
  - Gallery list with search, pagination, and bulk actions.
  - Gallery create/edit form with cover image upload.
  - Image dropzone and gallery grid with drag-to-reorder.
  - Image detail/edit modal (alt text, caption, delete).
- [ ] Compile to a self-contained bundle published under `public/vendor/gallery-vue/`.
- [ ] Provide a Blade view (`gallery::vue.manager`) that mounts the app into a named DOM element.
- [ ] Add `php artisan gallery:publish --vue` command.

---

## Phase 4 — React Management Pages

*Goal: Ship the same management experience for React consumers.*

- [ ] Add a `resources/js/gallery-react` build target using Vite.
- [ ] Mirror the Vue page/feature set with React components.
- [ ] Compile to `public/vendor/gallery-react/`.
- [ ] Provide a Blade view (`gallery::react.manager`) and a mounting helper.
- [ ] Add `php artisan gallery:publish --react` command.

---

## Phase 5 — Predefined Gallery Styles

*Goal: Provide ready-to-use gallery renderers that are independent of the manager UIs.*

- [ ] Define style presets as PHP classes implementing `GalleryStyle`:
  - `Grid`
  - `Masonry`
  - `Carousel`
  - `Slider`
  - `Lightbox`
- [ ] Add a `gallery:styles` config array to enable/disable presets.
- [ ] Ship minimal CSS files per style under `resources/css/gallery/`.
- [ ] Provide Blade components:
  - `<x-gallery::render :gallery="$gallery" style="masonry" />`
  - `<x-gallery::styles preset="carousel" />`
- [ ] Add JSON renderer endpoint for headless consumers:
  - `GET /api/v1/galleries/{gallery}?style=masonry`
- [ ] Ensure styles can be used without installing Vue or React.

---

## Phase 6 — Distribution & Developer Experience

*Goal: Make installation, customization, and upgrades straightforward.*

- [ ] Add `gallery:install` artisan command that publishes config, migrations, and optionally Vue/React assets.
- [ ] Document each installation path in `README.md`:
  - API-only
  - Vue manager
  - React manager
  - Style presets only
- [ ] Provide example host app integrations for Laravel Breeze, Jetstream, and Filament.
- [ ] Add upgrade notes to `CHANGELOG.md`.
- [ ] Automate asset builds in CI and attach them to releases.

---

## Suggested Package Pathway

```
jgawlik/laravel-gallery
├── src/
│   ├── Console/              # Install/publish commands
│   ├── Contracts/
│   │   └── GalleryStyle.php  # Style preset interface
│   ├── Http/
│   │   ├── Controllers/      # API + manager page controllers
│   │   ├── Requests/
│   │   └── Resources/
│   ├── Models/
│   ├── Policies/             # Authorization
│   ├── Services/
│   │   ├── ImageProcessor.php
│   │   └── StyleRegistry.php
│   ├── Styles/               # Built-in style presets
│   └── GalleryServiceProvider.php
├── resources/
│   ├── js/
│   │   ├── gallery-vue/      # Vue manager SPA
│   │   └── gallery-react/    # React manager SPA
│   ├── css/
│   │   └── gallery/          # Style preset CSS
│   └── views/
│       ├── layouts/          # Overridable manager layout stubs
│       ├── vue/
│       │   └── manager.blade.php
│       ├── react/
│       │   └── manager.blade.php
│       └── components/       # Blade style components
├── routes/
│   ├── api.php
│   └── web.php               # Manager + style preview routes
├── config/gallery.php
├── database/migrations/
└── tests/
    ├── Feature/
    ├── Browser/              # Dusk or Playwright tests for UIs
    └── Unit/
```

---

## Decision Log

| Decision | Rationale |
|----------|-----------|
| Vue and React are separate build targets | Lets consumers install only the runtime they use and keeps bundle sizes small. |
| Styles are backend-registered presets | Host apps can list, enable, and extend styles without touching frontend code. |
| Manager routes are web routes returning Blade views | The host app controls auth middleware and layout overrides; the SPA bundle mounts into a stable DOM node. |
| API remains the single source of truth | Both Vue and React managers consume identical endpoints, ensuring parity and easier maintenance. |

---

## Milestones

| Milestone | Target | Deliverable |
|-----------|--------|-------------|
| v1.0 | Current | Stable API, migrations, tests. |
| v1.1 | Phase 1 complete | Policies, pagination, reordering, image variants. Gate for UI work. |
| v1.2 | Phase 2 complete | Admin UI foundation and style registry. |
| v2.0 | Phase 3 complete | Vue management pages released. |
| v2.1 | Phase 4 complete | React management pages released. |
| v2.2 | Phase 5 complete | Predefined style presets and Blade components. |
| v3.0 | Phase 6 complete | Install command, docs, CI asset builds. |
