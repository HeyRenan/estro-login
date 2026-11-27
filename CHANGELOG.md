# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2026-11-27

### Fixed
- Fixed `body.logincraft.wp-core-ui .button-primary` selector in `assets/login.css` so the login submit button applies custom primary color and full width on `wp-login.php`.
- Replaced raw WP link-delete buttons (`Remover`, `Restaurar padrão`) with dedicated cohesive controls and aligned button rows.
- Fixed `"Sem gradiente"` placeholder clipping and matched color swatch/input heights (`38px`).
- Eliminated double focus halo on `wp-login.php` inputs and aligned WP 7.1 `.forgetmenot` help icon on dark cards.

### Improved
- Added `Desktop` / `Mobile` switcher in the Live Preview header (with realistic mobile collapse for `Split` layout).
- Upgraded boolean checkboxes to accessible CSS toggle switches, presets to a balanced grid with active highlight, and text inputs to full width.

## [1.0.0] - 2026-11-27

### Added
- Initial public release of **LoginCraft**.
- 3 layouts (`centered`, `split`, `minimal`) and 4 one-click presets (`Slate Light`, `Midnight Dark`, `Split Studio`, `Warm Minimal`).
- Real-time side-by-side Live Preview in **Settings → LoginCraft** built with zero-dependency Vanilla JS.
- Logo picker (with Custom Logo fallback), logo height control, background color/gradient/image + overlay, dark/light card support, and card border radius (`0px`, `8px`, `16px`).
- Automatic WCAG relative-luminance contrast calculation for buttons, cards, and links.
- UX & security options: welcome/footer text, hide language switcher, hide back-to-site link, generic failed-login message, sanitized Custom CSS, and nonce-protected Reset to Defaults.
- Developer filters: `logincraft_defaults`, `logincraft_options`, `logincraft_css_vars`, and `logincraft_error_message`.
- Translation template (`languages/logincraft.pot`) and Brazilian Portuguese translation (`languages/logincraft-pt_BR.l10n.php`).
- Standalone PHP test suite (`tests/run.php`) and GitHub Actions CI matrix (PHP 8.1–8.4).
