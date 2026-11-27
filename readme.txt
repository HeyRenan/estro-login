=== LoginCraft ===
Contributors: heyrenan
Tags: login, custom login, login page, white label, security
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight, zero-dependency WordPress login customizer with live preview, 3 layouts, WCAG auto-contrast, and security hygiene.

== Description ==

LoginCraft lets you brand `wp-login.php` cleanly without heavy builders, upsells, or frontend overhead.

= Features =

* 3 responsive layouts: Centered, Split, and Minimal
* 4 one-click starter presets (Slate Light, Midnight Dark, Split Studio, Warm Minimal)
* Real-time Vanilla JS preview in **Settings -> LoginCraft**
* Custom logo (falls back to Site Custom Logo) with adjustable height
* Solid color, optional gradient, and background image with overlay opacity control
* Automatic WCAG relative-luminance contrast for buttons, dark cards, and links
* Welcome message and footer note support
* Optional UX & security toggles: hide language switcher, hide Back to Site link, generic login error message
* Sanitized Custom CSS field and nonce-protected Reset to Defaults
* Clean footprint: `autoload = false` option and complete removal on uninstall

== Installation ==

1. Upload the `logincraft` folder to `/wp-content/plugins/` or install via the plugin ZIP.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Open **Settings -> LoginCraft** to customize your login screen.

== Frequently Asked Questions ==

= Does LoginCraft add any database queries or assets to the site frontend? =
No. Options are stored with `autoload = false`, admin assets load only on the LoginCraft settings screen, and login styles load only on `wp-login.php`.

= Does it work with WordPress session-expired modal (`interim-login`)? =
Yes. When `interim-login` is active, LoginCraft automatically keeps the layout compact so the modal stays usable.

== Changelog ==

= 1.0.0 =
* Initial public release.
