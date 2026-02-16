# Multisite Settings

WordPress Multisite settings are managed at two levels: **network-wide** (Super Admin) and **per-site** (Site Admin). Understanding which settings live where is key to managing a multisite installation.

## Network Settings (Settings → Network Settings)

These apply across all sites in the network:

### Operational Settings
- **Network Title** — displayed in the admin bar and network admin
- **Network Admin Email** — receives network-level notifications (new site registrations, upgrades)

### Registration Settings
- **Allow new registrations** — controls whether visitors can register accounts and/or create new sites
  - Disabled (default)
  - User accounts only
  - User accounts and new sites
- **Add New Users** — allows individual site admins to add new users (off by default; users must already exist on the network)
- **Limited Email Registrations** — restrict signups to specific email domains
- **Banned Email Domains** — block registrations from specific domains

### New Site Settings
- **Welcome Email** — template sent to new site admins
- **Welcome User Email** — template sent to newly registered users
- **First Post/Page/Comment** — default content created on every new site

### Upload Settings
- **Site upload space** — disk quota per site (default: 100 MB)
- **Upload file types** — allowed MIME types across the network
- **Max upload file size** — per-file limit (default: 1500 KB)

### Menu Settings
- **Enable administration menus** — toggle Plugins menu visibility for site admins (disabled by default for security)

## Per-Site Settings (Network Admin → Sites → Edit)

Super Admins can override settings on individual sites:

- **Info tab** — domain, path, registered date, last updated, attributes (public, archived, spam, deleted, mature)
- **Users tab** — manage site-specific users and roles
- **Themes tab** — enable/disable specific themes for this site
- **Settings tab** — raw `wp_options` editor for the site (advanced)

## wp-config.php Constants

Key constants that affect multisite behavior:

| Constant | Purpose | Default |
|----------|---------|---------|
| `MULTISITE` | Enables multisite | `false` |
| `SUBDOMAIN_INSTALL` | Subdomain vs subdirectory | `false` |
| `DOMAIN_CURRENT_SITE` | Primary domain | — |
| `PATH_CURRENT_SITE` | Primary path | `/` |
| `SITE_ID_CURRENT_SITE` | Network ID | `1` |
| `BLOG_ID_CURRENT_SITE` | Main site ID | `1` |
| `SUNRISE` | Load `sunrise.php` early (domain mapping) | `false` |
| `WP_DEFAULT_THEME` | Default theme for new sites | `twentytwentyfive` |

## WP-CLI

```bash
# List network settings
wp network meta list 1

# Get a specific network option
wp site option get registration

# Update network settings
wp site option update registration 'user'

# Per-site settings
wp option get blogname --url=site2.example.com
```

## Storage

- Network settings are stored in `wp_sitemeta` (network-level) and `wp_options` per site
- The Plugins menu is hidden from site admins by default — Super Admins must explicitly enable it in Network Settings
- Upload quotas are enforced per-site, not per-user

## Related

- [Multisite Architecture](./multisite.md) — core concepts, database structure, site switching
- [Network Admin](./network-admin.md) — Super Admin dashboard reference
- [Multisite Functions](./functions.md) — function reference
- [Multisite Hooks](./hooks.md) — action and filter reference
