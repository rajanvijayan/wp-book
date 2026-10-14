# Releasing WP Book

Git is the source of truth; WordPress.org SVN is updated only by GitHub Actions.

## One-time setup
Repository secrets (Settings → Secrets and variables → Actions):
- `SVN_USERNAME` — WordPress.org username
- `SVN_PASSWORD` — SVN password from WordPress.org Profile → Account & Security

## Release
1. Bump the version in the plugin header (`Version:`), any version constant, and `readme.txt` (`Stable tag:`).
2. Add a `== Changelog ==` entry in `readme.txt`.
3. Merge to the default branch, then tag without a `v` prefix:
   ```
   git tag 1.2.0 && git push origin 1.2.0
   ```
4. `deploy.yml` verifies the versions match, syncs `trunk`, creates `tags/1.2.0`,
   updates `/assets` from `.wordpress-org/`, and attaches the ZIP to a GitHub release.

## Readme / asset-only updates
Changes to `readme.txt` or `.wordpress-org/` on the default branch are pushed to SVN
by `assets.yml` without cutting a release (e.g. bumping "Tested up to").

Files listed in `.distignore` never reach SVN.
