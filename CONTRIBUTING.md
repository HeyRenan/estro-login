# Contributing to LoginCraft

Thanks for helping improve LoginCraft!

## Principles

- Keep the plugin lightweight and zero-dependency (no jQuery, no external CSS/JS frameworks, no frontend bloat).
- Maintain PHP 8.1+ compatibility with `declare(strict_types=1);`.
- Escape every dynamic output and sanitize every input.

## Local Checks

Run all checks before opening a pull request:

```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
node --check assets/admin.js
php tests/run.php
```

## Pull Requests

1. Fork the repository and create a branch from `main`.
2. Keep changes focused on a single concern and add or update tests in `tests/run.php`.
3. Verify `wp-login.php` visually across `centered`, `split`, `minimal`, mobile viewports, and `?interim-login=1`.
