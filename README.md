# Laravel Gallery

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jgawlik/laravel-gallery.svg?style=flat-square)](https://packagist.org/packages/jgawlik/laravel-gallery)
[![Total Downloads](https://img.shields.io/packagist/dt/jgawlik/laravel-gallery.svg?style=flat-square)](https://packagist.org/packages/jgawlik/laravel-gallery)

A plugin-like image gallery package for Laravel applications. Drop it into any Laravel project to get galleries and image uploads via a clean, modern API.

## Features

- Gallery CRUD API endpoints
- Multiple image uploads per gallery
- Configurable storage disk and path
- Automatic file cleanup on deletion
- Database migrations and model factories
- Full Pest test suite targeting 100% coverage
- PHPStan level 7 and Laravel Pint enforcement

## Requirements

- PHP ^8.3
- Laravel ^13.0

## Installation

Install the package via Composer:

```bash
composer require jgawlik/laravel-gallery
```

The service provider will be auto-discovered by Laravel.

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=gallery-config
```

The default config is:

```php
return [
    'disk' => env('GALLERY_DISK', 'public'),
    'path' => env('GALLERY_PATH', 'galleries'),
    'middleware' => ['api', 'auth'],
];
```

The default disk is `public`, so make sure the storage link exists:

```bash
php artisan storage:link
```

The `middleware` value controls route-level protection. If your app is API-only (e.g. Sanctum), change it to `['api', 'auth:sanctum']` or any middleware your project uses:

```php
'middleware' => ['api', 'auth:sanctum'],
```

Run the migrations:

```bash
php artisan migrate
```

## Usage

### API Endpoints

All gallery routes are authenticated by default. Galleries and images created through the API are owned by the authenticated user, and the default policies respond with a 404 when anyone else tries to view or modify them, so records can't be probed.

Ownership is enforced entirely through policies — route model binding is deliberately left unscoped. To expose galleries publicly (for example a guest-facing portfolio), register your own policy in a service provider and adjust the route middleware via the `gallery.middleware` config:

```php
use Illuminate\Support\Facades\Gate;
use Jgawlik\LaravelGallery\Models\Gallery;

Gate::policy(Gallery::class, YourGalleryPolicy::class);
```

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/galleries` | List all galleries |
| POST | `/api/v1/galleries` | Create a gallery |
| GET | `/api/v1/galleries/{gallery}` | Show a gallery |
| PUT/PATCH | `/api/v1/galleries/{gallery}` | Update a gallery |
| DELETE | `/api/v1/galleries/{gallery}` | Delete a gallery |
| POST | `/api/v1/galleries/{gallery}/images` | Upload images to a gallery |
| DELETE | `/api/v1/galleries/{gallery}/images/{image}` | Delete a gallery image |

### Creating a Gallery

```bash
curl -X POST http://your-app.test/api/v1/galleries \
  -H "Accept: application/json" \
  -F "name=Summer Photos" \
  -F "description=A simple gallery" \
  -F "image=@cover.jpg"
```

### Uploading Images

```bash
curl -X POST http://your-app.test/api/v1/galleries/1/images \
  -H "Accept: application/json" \
  -F "images[]=@photo1.jpg" \
  -F "images[]=@photo2.jpg"
```

## Testing

```bash
composer test
```

With coverage (requires [PCOV](https://pecl.php.net/package/pcov) or [Xdebug](https://xdebug.org)):

```bash
composer test:coverage
```

Or use Make:

```bash
make test-coverage
```

## Static Analysis & Formatting

```bash
composer analyse   # PHPStan
composer format    # Laravel Pint
composer lint      # Both
```

Or use Make:

```bash
make lint
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
