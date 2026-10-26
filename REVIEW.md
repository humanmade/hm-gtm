# Review

## What failure looks like here
- Fatal PHP errors on the front end or in the editor for sites running the plugin (see #47/#48).
- Unescaped output in admin settings or the GTM script/dataLayer tags (security review, #50).
- A release whose tag, plugin header and `VERSION` constant disagree, or a tag that is moved after Packagist has seen it.

## Always check
- Output in `inc/` is escaped, and any `phpcs:ignore` has a reason that holds up.
- `dataLayer` shape changes are backwards compatible or called out as breaking.
- Release workflow: user input goes through `env:`, not inline `${{ }}` in `run:`; tags are created once and never force-pushed.
- New top-level files are either meant to ship or listed as `export-ignore` in `.gitattributes`.

## Paths that need a person
- `.github/workflows/`
- `.gitattributes` (defines the shipped ZIP and the Composer dist)
- `inc/namespace.php` settings and sanitize callbacks

## Skip
- None (no generated, vendored or lock files are tracked).

## Nits
Cap 3. Docs commands must work on macOS (BSD) and Linux.

## Learned
- 2026-10-26: Tag 3.1.4 shipped with `Version: 3.1.3` in plugin.php; version drift between tag and header is the bug class the release workflow exists to stop.
