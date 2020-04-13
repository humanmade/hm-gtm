# Changelog

The changelog is now included in [GitHub Releases](https://github.com/humanmade/hm-gtm/releases).

## Prior versions

v2.0.2

- Fix fatal error on user signup / account activation

v2.0.1

- Fix author output on post types that don't support them #17

v2.0.0

This major version contains breaking changes for any projects that reference the `HM_GTM` namespace or the function `HM_GTM\tag()`.

Additionally the `dataLayer` format has been completely overhauled to be more useful.

- Fully rewritten API
- Namespace changed to `HM\GTM`
- Custom event tracking JS added
- `dataLayer` completely overhauled
- New non-namespaced template tags, `gtm_tag()`, `get_gtm_data_layer()`, `get_gtm_data_attributes()`

v1.1.0

Initial version
