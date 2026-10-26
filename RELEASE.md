# Release Process

This document describes the automated release process for the HM GTM plugin.

## Overview

Releases are cut by the **`Release`** GitHub Actions workflow (`.github/workflows/release.yml`), triggered manually from the Actions tab.

The workflow does everything *before* the tag exists, then creates the tag once and never touches it again:

1. Validates the version you supplied (must be `X.Y.Z`).
2. Checks the tag does not already exist and that `plugin.php` contains the `__VERSION__` placeholder.
3. Stamps the version into `plugin.php` (replaces `__VERSION__` in the `Version:` header and the `VERSION` constant) and fails if any placeholder is left.
4. Commits the stamped version and **creates an annotated tag `X.Y.Z` pointing at that commit**.
5. Pushes the tag — once, never force-pushed.
6. Builds the distribution ZIP with `git archive` and publishes a GitHub release with auto-generated notes.

Because the tag is created already versioned, it is **immutable**. This is what Packagist requires: Packagist rejects tag updates, so the tag must never be moved after it is first published.

## Creating a release

1. Make sure `master` is green and contains the code you want to ship.
2. Go to **Actions → Release → Run workflow**.
3. Enter the version (e.g. `1.2.3`). Tags have no `v` prefix.
4. Run it.

The workflow will fail fast if the tag already exists — tags are immutable, so bump the version instead of trying to re-release.

## Version numbering

Follow [Semantic Versioning](https://semver.org/):

- **Major** (1.0.0 → 2.0.0): Breaking changes
- **Minor** (1.0.0 → 1.1.0): New features, backwards compatible
- **Patch** (1.0.0 → 1.0.1): Bug fixes, backwards compatible

The version on `master` is always the literal placeholder `__VERSION__`; the real number only ever exists inside a release tag. This keeps `master` free of version churn and guarantees the version in a tag matches the tag name.

## What gets released

The ZIP is produced with `git archive`, which honours the `export-ignore` rules in `.gitattributes`. That file is the **single source of truth** for what ships. Currently included:

- `plugin.php` (with the version stamped in)
- `assets/`
- `inc/`
- `composer.json`
- `LICENSE`

Everything marked `export-ignore` in `.gitattributes` (workflow files, docs, changelog) is excluded. To change what ships, edit `.gitattributes` — nothing in the workflow needs to change.

## Packagist

Packagist publishes new tags automatically (via the GitHub webhook / auto-update). Since every `X.Y.Z` tag is immutable and self-contained, no manual intervention or tag rewriting is ever required. If a release was wrong, **publish a new patch version** rather than attempting to move a tag.

## Troubleshooting

### "Tag already exists"

This is by design — tags are immutable. Bump to the next version and run the workflow again.

### "plugin.php does not contain the __VERSION__ placeholder"

The `Version:` header and `VERSION` constant in `plugin.php` on `master` must both be `__VERSION__`. Restore the placeholder and run the workflow again.

### Tag pushed but the release failed

The tag is immutable, so do not bump the version or move the tag. Rebuild the ZIP from the existing tag with `git archive --format=zip --prefix=hm-gtm/ -o hm-gtm-X.Y.Z.zip X.Y.Z`, then create the release with `gh release create X.Y.Z hm-gtm-X.Y.Z.zip --title X.Y.Z --generate-notes`.

### ZIP missing files / contains dev files

Adjust the `export-ignore` entries in `.gitattributes`. The ZIP is generated straight from the tagged tree via `git archive`, so it always matches what is in the tag.

## Manual release (fallback)

If you ever need to cut a release by hand:

```bash
# from a clean checkout of the commit you want to ship
perl -pi -e 's/__VERSION__/1.2.3/g' plugin.php
git add plugin.php
git commit -m "Release 1.2.3"
git tag -a 1.2.3 -m "Release 1.2.3"
git push origin refs/tags/1.2.3          # push the tag ONCE; never force-push

# package it
git archive --format=zip --prefix=hm-gtm/ -o hm-gtm-1.2.3.zip 1.2.3
```

Then attach the ZIP to a GitHub release created from the tag.
