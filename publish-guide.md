# Publishing — letmesendemail-laravel

## Registry

[Packagist](https://packagist.org) — `letmesendemail/letmesendemail-laravel`

Package URL: `https://packagist.org/packages/letmesendemail/letmesendemail-laravel`

## How Versioning Works

Packagist derives the package version from the Git tag. Do not add a `version`
field to `composer.json`. The tag must match `vX.Y.Z` format (e.g., `v0.2.0`).

## Maintainer Prerequisites

1. An account on [packagist.org](https://packagist.org) that is a maintainer of the `letmesendemail/letmesendemail-laravel` package.
2. GitHub repository access with push permission for the `letmesendemail/letmesendemail-laravel` repository.
3. If using GitHub Actions for automated publishing, a Packagist API token stored as a repository secret named `PACKAGIST_TOKEN`.

## First-Time Setup

1. Go to [packagist.org/packages/submit](https://packagist.org/packages/submit)
2. Enter the repository URL: `https://github.com/letmesendemail/letmesendemail-laravel`
3. Packagist auto-detects the package from `composer.json`.
4. Enable auto-update in package settings so new tags publish automatically.
5. (Optional) Configure a GitHub Actions workflow or Packagist webhook for automated publishing.

## Pre-Release Validation

Before tagging a release, run all validation checks from the repository root:

```bash
composer validate --strict
vendor/bin/php-cs-fixer fix --dry-run --diff
vendor/bin/phpstan analyse
vendor/bin/pest
```

Fix any failures before proceeding.

## Releasing a Version

```bash
# 1. Ensure CHANGELOG.md is updated (move Unreleased entries to a new version section)
# 2. Commit all changes
# 3. Tag and push both master and the tag
git tag v<version>
git push origin master v<version>
```

Packagist picks it up automatically when auto-update is enabled.

## Creating a GitHub Release (optional)

1. Go to the repository's Releases page.
2. Click "Draft a new release".
3. Select the existing tag.
4. Add release notes summarizing changes from CHANGELOG.md.
5. Mark it as the latest release and publish.

## Manual Publishing

If auto-update is not configured, update the package manually:

1. Go to `https://packagist.org/packages/letmesendemail/letmesendemail-laravel`
2. Click "Update" to fetch the latest tag.

## Verifying

```bash
composer require letmesendemail/letmesendemail-laravel
```

Then check that the installed version matches the released tag.

## Recovering a Broken Release

- **Packagist does not support deleting versions.** Instead, publish a patch release with the fix.
- If a version must be removed from visibility, use the "Disable" action on Packagist.
- For critical security issues, contact Packagist support to request removal.
