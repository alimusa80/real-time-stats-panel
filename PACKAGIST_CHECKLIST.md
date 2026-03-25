# Packagist And Filament Submission Checklist

Use this checklist before publishing `alimusa/real-time-stats-panel`.

## Repository

- Create repository: `https://github.com/alimusa80/real-time-stats-panel`
- Push package contents to the repository root
- Do not push a wrapper folder that contains `real-time-stats-panel/` inside the repo root
- `README.md` and `composer.json` must be visible at the top level of the GitHub repository
- Add repository description:
  - `Production-ready real-time dashboard metrics plugin for FilamentPHP`
- Add website URL:
  - `https://www.alimusa.so/`
- Add topics:
  - `filament`
  - `filamentphp`
  - `laravel`
  - `livewire`
  - `dashboard`
  - `websockets`
  - `realtime`
  - `plugin`
- Ensure repository is public

## Required Files

- `README.md`
- `LICENSE.md`
- `CHANGELOG.md`
- `CONTRIBUTING.md`
- `SECURITY.md`
- `.gitignore`
- `composer.json`

## Package Metadata

- Composer package name is correct
- GitHub source URL is correct
- GitHub issues URL is correct
- Author details are correct
- Keywords are present
- Branch alias is present

## Visual Assets

- `art/banner.svg` is ready for GitHub and package landing pages
- `art/widget-preview.svg` is ready for documentation and plugin listing screenshots
- Replace SVG previews with real dashboard screenshots later if available

## Validation

Run:

```bash
composer validate --no-check-publish
composer install
composer test
```

## Packagist

1. Sign in to Packagist.
2. Click `Submit`.
3. Enter repository URL:
   - `https://github.com/alimusa80/real-time-stats-panel`
4. Submit package.
5. Confirm that Packagist detects:
   - package name
   - version tags
   - license
   - autoload rules
   - root-level `composer.json`
6. Configure auto-update webhook if needed.

## First Stable Tag

Recommended first stable release:

```bash
git tag v1.0.0
git push origin v1.0.0
```

## Filament Plugin Directory

Before submitting through the Filament author flow:

- confirm package is installable from Packagist
- confirm public docs are live
- confirm screenshots are present
- confirm compatibility claims are accurate
- confirm plugin name and description are final
- confirm pricing model:
  - free
  - paid
- prepare listing summary:
  - what it does
  - supported Filament versions
  - supported Laravel versions
  - main features

## Post-publish

- test fresh install in a clean Laravel app
- test Filament dashboard rendering
- test broadcasting auth
- test Soketi or Pusher connection
- announce release on GitHub and your website
