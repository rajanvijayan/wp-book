# Releasing BookPress (slug `wp-book`)

Git is the source of truth; WordPress.org SVN is updated only by GitHub Actions.

## One-time setup
Repository secrets (Settings → Secrets and variables → Actions):
- `SVN_USERNAME` — WordPress.org username
- `SVN_PASSWORD` — SVN password from WordPress.org Profile → Account & Security

## Release
1. On the release branch, bump the version in the plugin header (`Version:`), `WPBOOK_VERSION`
   in `wp-book.php`, `readme.txt` (`Stable tag:`) and `package.json`/`package-lock.json`.
2. Add a `== Changelog ==` entry (and an `== Upgrade Notice ==` for major changes) in `readme.txt`.
3. Open a PR to `main` and wait for CI to pass: PHPCS, ESLint, build on PHP 7.4–8.4, PHPUnit and
   Plugin Check.
4. **Tag the PR's head commit first, then merge.** Tags have no `v` prefix:
   ```
   git tag 2.0.0 <head-commit> && git push origin 2.0.0
   ```
   Wait for the "Deploy to WordPress.org" run to finish, then merge the PR.

   Why this order: a merge to `main` that touches `readme.txt` runs `assets.yml`, which updates
   `trunk/readme.txt` straight away. If the new `Stable tag` does not exist in SVN yet, WordPress.org
   serves `trunk`, which still holds the previous release's code. Tagging first creates `tags/X.Y.Z`
   and updates `trunk` together; the later merge then has nothing left to deploy.
5. `deploy.yml` verifies the versions match, installs Composer dependencies (Strauss prefixing and
   font trimming run in `post-install`), builds the admin app, runs the PDF smoke test, syncs `trunk`,
   creates `tags/X.Y.Z`, updates `/assets` from `.wordpress-org/`, and attaches the ZIP to a GitHub
   release.

Only exact `X.Y.Z` tags deploy; a tag such as `2.1.0-rc.1` does nothing.

## Dependencies
`vendor/`, `vendor-prefixed/` and `build/` are not committed; `deploy.yml` builds them.

- `config.platform.php` is pinned to 7.4, the plugin's minimum PHP version, so `composer update`
  never locks a dependency that needs a newer PHP than "Requires PHP" promises.
- Commit `composer.lock` and `package-lock.json` so release builds are reproducible.
- mPDF, its QR code library and their dependencies are prefixed into `WPBook\Vendor\` by Strauss
  (`bin/strauss.phar`, downloaded on first install). Action Scheduler is not prefixed.

### Font packs after an mPDF update
The plugin ships only mPDF's DejaVu fonts. `bin/trim-fonts.php` removes the rest after Strauss and
checks them against `src/render/font-packs.php`, the manifest that the font-pack downloader uses.
If an mPDF update changes its fonts, `composer install` stops with "Font manifest is out of date".
Refresh it:
```
rm -rf vendor-prefixed
WPBOOK_KEEP_FONTS=1 composer install
php bin/trim-fonts.php --write-manifest
composer install
```
Commit the new `src/render/font-packs.php`. Downloads come from the matching mPDF tag on GitHub.

## Tests
```
bin/install-wp-tests.sh wordpress_test root '' localhost latest
WP_TESTS_DIR=/tmp/wordpress-tests-lib vendor/bin/phpunit
```

## Readme / asset-only updates
Changes to `readme.txt` or `.wordpress-org/` on `main` are pushed to SVN by `assets.yml` without
cutting a release (e.g. bumping "Tested up to"). Don't change `Stable tag` this way.

Files listed in `.distignore` never reach SVN.
