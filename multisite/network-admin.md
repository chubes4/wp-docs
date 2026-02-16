# Network Admin (Multisite)

The Network Admin dashboard is the Super Admin control center for managing all sites, users, themes, and plugins across a WordPress Multisite network. Access it via **My Sites → Network Admin** in the admin bar.

## Who Can Access It?

Only **Super Admins** — a role that exists exclusively in Multisite. Super Admin is not a regular WordPress role; it's a flag stored in `wp_sitemeta` under `site_admins`. Regular Administrators are scoped to their individual sites.

```php
// Check Super Admin status
if ( is_super_admin() ) {
    // Has network-wide privileges
}

// Capability check for network admin screens
if ( current_user_can( 'manage_network' ) ) {
    // Can access Network Admin
}
```

## Network Admin Screens

### Dashboard
- Network-wide notices and WordPress update status
- "Right Now" widget: total sites, users, active themes/plugins
- Search: quickly find sites or users across the network

### Sites (Sites → All Sites)
The core of network management:
- **Add New** — create sites with domain/path, title, admin email
- **Edit** — per-site Info, Users, Themes, Settings tabs
- **Actions** — Deactivate, Archive, Spam, Delete, Visit, Dashboard

Site attributes:

| Attribute | Effect |
|-----------|--------|
| Public | Included in search engines and site listings |
| Archived | Front-end shows "archived" message; admin accessible |
| Spam | Completely inaccessible; used for abuse |
| Deleted | Soft-delete; recoverable by Super Admin |
| Mature | Flagged for mature content (if network uses content warnings) |

### Users (Users → All Users)
- View all users across the entire network
- **Add New** — creates a network-level user (can then be added to specific sites)
- **Super Admin** toggle — grant/revoke via Edit User
- Users can belong to multiple sites with different roles on each

### Themes (Themes → Installed Themes)
- **Network Enable/Disable** — themes must be network-enabled before individual sites can activate them
- Network-active themes are available to all sites
- Per-site theme overrides available via Sites → Edit → Themes tab
- Only Super Admins can install new themes

### Plugins (Plugins → Installed Plugins)
- **Network Activate** — plugin runs on ALL sites (no per-site toggle)
- Non-network-activated plugins can be activated per-site (if Plugins menu is enabled for site admins)
- Only Super Admins can install/delete plugins
- Network-activated plugins load before site-activated ones

### Settings (Settings → Network Settings)
See the [Multisite Settings](./settings.md) doc for full details.

### Updates (Updates)
- WordPress core, theme, and plugin updates for the entire network
- Updates apply to all sites simultaneously
- Database upgrades (`/wp-admin/upgrade.php`) must run per-site after major core updates — WP-CLI: `wp core update-db --network`

## Network Admin Hooks

The Network Admin uses its own set of hooks, separate from the site admin:

```php
// Network admin menu (not admin_menu)
add_action( 'network_admin_menu', function() {
    add_menu_page( 'Custom', 'Custom', 'manage_network', 'custom-page', 'render_fn' );
});

// Network admin notices
add_action( 'network_admin_notices', function() {
    echo '<div class="notice notice-info"><p>Network notice</p></div>';
});

// Network admin init
add_action( 'network_admin_init', 'my_network_init' );
```

### Key Hooks

| Hook | Purpose |
|------|---------|
| `network_admin_menu` | Register network admin menu pages |
| `network_admin_notices` | Display notices in network admin |
| `network_admin_init` | Runs on network admin page load |
| `network_admin_edit_{action}` | Handle network admin form submissions |
| `wpmuadminedit` | Legacy hook for network admin actions |

## WP-CLI for Network Admin Tasks

```bash
# List all sites
wp site list --fields=blog_id,url,registered

# Create a new site
wp site create --slug=newsite --title="New Site" --email=admin@example.com

# Delete a site
wp site delete 4 --yes

# List Super Admins
wp super-admin list

# Grant Super Admin
wp super-admin add username

# Revoke Super Admin
wp super-admin remove username

# Network-activate a plugin
wp plugin activate my-plugin --network

# Network-enable a theme
wp theme enable flavor --network

# Run DB upgrades across all sites
wp core update-db --network
```

## Key Differences: Network Admin vs Site Admin

| Capability | Network Admin (Super) | Site Admin |
|-----------|:----:|:----:|
| Install plugins/themes | ✅ | ❌ |
| Network activate plugins | ✅ | ❌ |
| Create/delete sites | ✅ | ❌ |
| Manage all users | ✅ | Own site only |
| Access Network Settings | ✅ | ❌ |
| Edit theme files | ✅ | ❌ |
| Add existing users to site | ✅ | If enabled |
| Activate site-level plugins | ✅ | If Plugins menu enabled |

## URL Structure

The Network Admin is at `/wp-admin/network/` — distinct from any individual site's `/wp-admin/`:

```php
// Get network admin URL
$url = network_admin_url( 'sites.php' );
// → https://example.com/wp-admin/network/sites.php

// Check if in network admin
if ( is_network_admin() ) {
    // Currently in Network Admin context
}
```

## Related

- [Multisite Architecture](./multisite.md) — core concepts, database structure, site switching
- [Multisite Settings](./settings.md) — network and per-site settings reference
- [Multisite Functions](./functions.md) — function reference
- [Multisite Hooks](./hooks.md) — action and filter reference
