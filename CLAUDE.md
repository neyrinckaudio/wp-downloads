# PN Downloads - WordPress Plugin

WordPress plugin for managing product installer downloads with a REST API for automated version updates.

## Architecture

- **Custom Post Type**: `pn_download` — each post is a product. Post title = product name, post slug = API identifier.
- **No frontend rendering** — Divi 5 Theme Builder handles display. Shortcodes provide version text and download URLs.
- **REST API** at `/wp-json/pn-downloads/v1/` for programmatic updates and tracked downloads.

## Post Meta Fields

Version and URL fields are per-platform so Mac and Windows can be updated independently.

| Key | Purpose |
|---|---|
| `pn_dl_mac_version` | macOS display version (e.g. 3.2.1) |
| `pn_dl_mac_version_exact` | macOS exact build version (e.g. 3.2.1.18) |
| `pn_dl_mac_url` | macOS installer URL |
| `pn_dl_win_version` | Windows display version (e.g. 3.2.1) |
| `pn_dl_win_version_exact` | Windows exact build version (e.g. 3.2.1.18) |
| `pn_dl_win_url` | Windows installer URL |
| `pn_dl_legacy` | Serialized array of legacy versions `[{mac_version, mac_version_exact, mac_url, win_version, win_version_exact, win_url}]` |
| `pn_dl_mac_count` | macOS download count |
| `pn_dl_win_count` | Windows download count |

## REST API Endpoints

- `GET /products/{slug}` — public, returns product info
- `POST /products/{slug}` — authenticated, updates version/URLs. Body fields: `mac_version`, `mac_version_exact`, `mac_url`, `win_version`, `win_version_exact`, `win_url`, `archive_current` (bool).
- `GET /download/{slug}/{mac|win}` — public, increments download count and 302 redirects to file URL

## Authentication

- Custom API key via `X-PN-API-Key` header (not WP application passwords)
- Optional IP allowlist (empty = allow all, for travel)
- Settings at Settings > PN Downloads

## Shortcodes

- `[pn_download_version platform="mac"]` — display version for platform
- `[pn_download_version_exact platform="mac"]` — exact build version for platform
- `[pn_download_url platform="mac"]` — tracked download redirect URL

All shortcodes accept optional `slug` attribute. On a `pn_download` post, slug is automatic.

## Frontend Integration

Use shortcodes in any theme or page builder for version display. For download buttons, use the tracked download URL directly in button URL fields:

```
/wp-json/pn-downloads/v1/download/{slug}/{mac|win}
```

