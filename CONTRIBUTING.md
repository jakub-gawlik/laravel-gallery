# Contributing

Thank you for considering contributing to `jgawlik/laravel-gallery`!

## Getting Started

1. Fork the repository.
2. Clone your fork locally.
3. Install dependencies with `composer install`.
4. Create a new branch for your feature or bug fix.

## Development Workflow

Run the full quality suite before committing:

```bash
make lint     # Laravel Pint + PHPStan
make test     # Pest test suite
```

## Code Style

This project uses [Laravel Pint](https://laravel.com/docs/pint). You can auto-fix style issues with:

```bash
composer format
# or
make format
```

## Static Analysis

PHPStan is configured at level 7. Run it with:

```bash
composer analyse
# or
make analyse
```

## Testing

Tests are written with [Pest](https://pestphp.com/). Aim to keep 100% coverage for any new code:

```bash
composer test:coverage
# or
make test-coverage
```

> Note: a coverage driver such as [PCOV](https://pecl.php.net/package/pcov) or [Xdebug](https://xdebug.org) must be installed to generate coverage reports.

## Pull Requests

- Keep changes focused and well-scoped.
- Add or update tests for any changed behaviour.
- Update `CHANGELOG.md` under the `Unreleased` section.
- Ensure the full quality suite passes locally.

## Code of Conduct

Be respectful, constructive, and inclusive in all interactions.
