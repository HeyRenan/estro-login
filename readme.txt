=== Estro Login ===
Contributors: heyrenan
Tags: login, custom login, login page, white label, security
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight, zero-dependency WordPress login customizer with live preview, 3 layouts, WCAG auto-contrast, and security hygiene.

== Description ==

Estro Login lets you brand `wp-login.php` cleanly without heavy builders, upsells, or frontend overhead.

= Features =

* 3 responsive layouts: Centered, Split, and Minimal
* 4 one-click starter presets (Slate Light, Midnight Dark, Split Studio, Warm Minimal)
* Real-time Vanilla JS preview in **Settings -> Estro Login**
* Custom logo (falls back to Site Custom Logo) with adjustable height
* Solid color, optional gradient, and background image with overlay opacity control
* Automatic WCAG relative-luminance contrast for buttons, dark cards, and links
* Welcome message and footer note support
* Optional UX & security toggles: hide language switcher, hide Back to Site link, generic login error message
* Sanitized Custom CSS field, JSON settings Export/Import, and nonce-protected Reset to Defaults
* Clean footprint: `autoload = false` option and complete removal on uninstall

== Installation ==

1. Upload the `estro-login` folder to `/wp-content/plugins/` or install via the plugin ZIP.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Open **Settings -> Estro Login** to customize your login screen.

== Frequently Asked Questions ==

= Does Estro Login add any database queries or assets to the site frontend? =
No. Options are stored with `autoload = false`, admin assets load only on the Estro Login settings screen, and login styles load only on `wp-login.php`.

= Does it work with WordPress session-expired modal (`interim-login`)? =
Yes. When `interim-login` is active, Estro Login automatically keeps the layout compact so the modal stays usable.

== Screenshots ==

1. Estro Login settings page with starter presets and real-time Split Studio live preview.
2. Responsive Split Studio login layout on `wp-login.php`.
3. Midnight Dark login preset with automatic WCAG dark-card contrast.
4. Warm Minimal login preset on `wp-login.php`.

== Changelog ==

= 1.2.0 =
* Rebranded from LoginCraft to **Estro Login** (`estro-login`) as part of the Estro plugin suite.
* Added `Update URI: false` header to prevent WordPress.org slug collisions with unrelated plugins.

= 1.1.0 =
* Added JSON Export & Import for portable settings across sites (excluding site-specific media IDs).
* Served `full` attachment resolution for crisp Retina/HiDPI logos on `wp-login.php`.
* Fixed Minimal layout icon contrast for WordPress 7.1 Remember Me help button on dark backgrounds.
* Fixed 3-character hex color swatch synchronization and added Composer (`heyrenan/estro-login`) plus tag-triggered release automation.

= 1.0.1 =
* Fixed `.wp-core-ui` body selector so the primary login button always applies custom color and full width.
* Redesigned admin buttons (Select, Remove, Clear, Reset), preset grid with active state, toggle switches, full-width inputs, and color input alignment.
* Added Desktop / Mobile viewport switcher to the live preview.

= 1.0.0 =
* Initial public release.
