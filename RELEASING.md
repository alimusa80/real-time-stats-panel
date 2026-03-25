# Releasing

This document describes the recommended release flow for `alimusa/real-time-stats-panel`.

## Versioning

Use semantic versioning:

- `1.0.0` for the first stable public release
- `1.0.1` for backwards-compatible bug fixes
- `1.1.0` for backwards-compatible features
- `2.0.0` for breaking changes

## Branch strategy

- `main` is the stable branch
- tag releases directly from `main`
- keep `CHANGELOG.md` updated before each tag

## Pre-release checklist

1. Run package checks:

```bash
composer validate --no-check-publish
composer install
composer test
```

2. Review docs:

- verify `README.md`
- verify installation steps
- verify compatibility table
- verify screenshots in `art/`

3. Review metadata:

- package name
- support URLs
- author details
- license
- changelog entry

## Tagging a release

Example for version `1.0.0`:

```bash
git checkout main
git pull origin main
git add .
git commit -m "Release v1.0.0"
git tag v1.0.0
git push origin main
git push origin v1.0.0
```

## GitHub release notes

When creating the GitHub release:

- title: `v1.0.0`
- tag: `v1.0.0`
- target: `main`
- summary:
  - new features
  - fixes
  - compatibility notes
  - upgrade notes if needed

## Packagist behavior

Packagist will pick up new versions from Git tags once the repository webhook or auto-update is configured.

Recommended tag format:

- `v1.0.0`
- `v1.0.1`
- `v1.1.0`

Avoid:

- unprefixed inconsistent tags
- force-moving release tags
- tagging before updating changelog and docs
