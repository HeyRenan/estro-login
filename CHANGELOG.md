# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
