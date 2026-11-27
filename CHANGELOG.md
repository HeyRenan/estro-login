# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-11-27

### Changed
- Rebranded from **LoginCraft** (`logincraft`) to **Estro Login** (`estro-login`) under the **Estro** plugin suite.
- Renamed option key (`estro_login_settings`), hooks (`estro_login_*`), constants (`ESTRO_LOGIN_*`), namespace (`EstroLogin`), and CSS/JS identifiers.
- Added `Update URI: false` plugin header to prevent WordPress.org directory slug collisions.

## [1.1.0] - 2026-11-27

### Added
- Client-side JSON **Export & Import** in **Settings → Estro Login** to transfer layout, colors, text, toggles, and Custom CSS across sites (ignoring site-specific media IDs).
- `composer.json` (`type: wordpress-plugin`) for Composer/Bedrock workflows.
- GitHub Actions release workflow (`.github/workflows/release.yml`) triggered on `v*` tags to validate, build clean `estro-login.zip`, and publish GitHub Releases.
- Real browser screenshots in `README.md` (`docs/screenshots/` excluded from release ZIPs).

### Fixed
- Request `full` logo resolution in `LoginRenderer::resolveLogoUrl()` for sharp Retina/HiDPI logos.
- Sync 3-character hex values (e.g. `#abc` → `#aabbcc`) to native color swatches in `assets/admin.js` and sanitize image URL characters in preview CSS custom properties.
- Inherit `--el-outer-text` on WordPress 7.1 `.forgetmenot` help icon and button in Minimal layout on dark backgrounds.

## [1.0.1] - 2026-11-27

### Fixed
- Fixed `body.estro-login.wp-core-ui .button-primary` selector in `assets/login.css` so the login submit button applies custom primary color and full width on `wp-login.php`.
- Replaced raw WP link-delete buttons (`Remover`, `Restaurar padrão`) with dedicated cohesive controls and aligned button rows.
- Fixed `"Sem gradiente"` placeholder clipping and matched color swatch/input heights (`38px`).
- Eliminated double focus halo on `wp-login.php` inputs and aligned WP 7.1 `.forgetmenot` help icon on dark cards.

### Improved
- Added `Desktop` / `Mobile` switcher in the Live Preview header (with realistic mobile collapse for `Split` layout).
- Upgraded boolean checkboxes to accessible CSS toggle switches, presets to a balanced grid with active highlight, and text inputs to full width.

## [1.0.0] - 2026-11-27

### Added
- Initial public release of **Estro Login**.
- 3 layouts (`centered`, `split`, `minimal`) and 4 one-click presets (`Slate Light`, `Midnight Dark`, `Split Studio`, `Warm Minimal`).
- Real-time side-by-side Live Preview in **Settings → Estro Login** built with zero-dependency Vanilla JS.
- Logo picker (with Custom Logo fallback), logo height control, background color/gradient/image + overlay, dark/light card support, and card border radius (`0px`, `8px`, `16px`).
- Automatic WCAG relative-luminance contrast calculation for buttons, cards, and links.
- UX & security options: welcome/footer text, hide language switcher, hide back-to-site link, generic failed-login message, sanitized Custom CSS, and nonce-protected Reset to Defaults.
- Developer filters: `estro_login_defaults`, `estro_login_options`, `estro_login_css_vars`, and `estro_login_error_message`.
- Translation template (`languages/estro-login.pot`) and Brazilian Portuguese translation (`languages/estro-login-pt_BR.l10n.php`).
- Standalone PHP test suite (`tests/run.php`) and GitHub Actions CI matrix (PHP 8.1–8.4).
