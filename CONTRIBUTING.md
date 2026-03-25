# Contributing

## Workflow

1. Fork the repository.
2. Create a feature branch from `main`.
3. Keep changes focused and documented.
4. Add or update tests when behavior changes.
5. Open a pull request with a concise summary and reproduction steps when applicable.

## Standards

- Follow PSR-12 and Laravel package conventions.
- Preserve Filament panel plugin compatibility.
- Prefer backwards-compatible changes for public APIs.
- Keep README and changelog entries in sync with shipped behavior.

## Local checks

```bash
composer install
composer test
```

If you change Filament assets or screenshots, update the package documentation before submitting.
