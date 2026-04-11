# PN Downloads

WordPress plugin for managing product installer downloads with a REST API for automated version updates.

## Features

- Custom post type for products with per-platform version and URL fields
- REST API for programmatic version updates (e.g. from CI/CD)
- Tracked downloads with per-platform counts and 302 redirects
- Shortcodes for displaying version info in any theme or page builder
- API key authentication with optional IP allowlist

## Installation

1. Copy the `pn-downloads` folder to `wp-content/plugins/`
2. Activate the plugin in WordPress admin
3. An API key is generated automatically on activation — find it at **Settings > PN Downloads**

## Usage

### Creating a Product

Go to **Downloads > Add New** in the WordPress admin. The post title is the product name, and the post slug becomes the API identifier.

Each product has independent version and URL fields for macOS and Windows.

### Download Buttons

Use the tracked download URL as a button link in any theme or page builder:

```
/wp-json/pn-downloads/v1/download/{slug}/mac
/wp-json/pn-downloads/v1/download/{slug}/win
```

This increments the download count and redirects to the installer file.

### Shortcodes

Display version information in any text content:

```
[pn_download_version platform="mac"]
[pn_download_version_exact platform="mac"]
```

All shortcodes accept an optional `slug` attribute. On a product post page, the slug is detected automatically.

### REST API

**Get product info** (public):
```bash
curl https://yoursite.com/wp-json/pn-downloads/v1/products/{slug}
```

**Update a product** (authenticated):
```bash
curl -X POST https://yoursite.com/wp-json/pn-downloads/v1/products/{slug} \
  -H "X-PN-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "mac_version": "3.2.1",
    "mac_version_exact": "3.2.1.18",
    "mac_url": "https://example.com/installer.pkg"
  }'
```

Set `"archive_current": true` to push the current version to the legacy list before updating.

## License

Proprietary - Neyrinck Audio
