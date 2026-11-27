<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/../');
define('ESTRO_LOGIN_VERSION', '1.2.0');
define('ESTRO_LOGIN_FILE', dirname(__DIR__) . '/estro-login.php');
define('ESTRO_LOGIN_DIR', dirname(__DIR__) . '/');
define('ESTRO_LOGIN_URL', 'https://example.test/wp-content/plugins/estro-login/');
define('ESTRO_LOGIN_OPTION', 'estro_login_settings');

$GLOBALS['el_test_options'] = [];
$GLOBALS['el_test_theme_mods'] = [];
$GLOBALS['el_test_attachments'] = [];
$GLOBALS['el_test_attachment_sizes'] = [];
$GLOBALS['el_test_actions'] = [];
$GLOBALS['el_test_filters'] = [];
$GLOBALS['el_test_enqueued_styles'] = [];
$GLOBALS['el_test_inline_styles'] = [];
$GLOBALS['el_test_can_manage'] = true;

function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
{
    foreach ($GLOBALS['el_test_filters'][$hook] ?? [] as $callback) {
        $value = $callback($value, ...$args);
    }

    return $value;
}

function add_action(string $hook, callable|array $callback, int $priority = 10, int $args = 1): bool
{
    $GLOBALS['el_test_actions'][$hook][] = $callback;
    return true;
}

function add_filter(string $hook, callable|array $callback, int $priority = 10, int $args = 1): bool
{
    $GLOBALS['el_test_filters'][$hook][] = $callback;
    return true;
}

function plugin_basename(string $file): string
{
    return 'estro-login/estro-login.php';
}

function get_option(string $name, mixed $default = false): mixed
{
    return array_key_exists($name, $GLOBALS['el_test_options'])
        ? $GLOBALS['el_test_options'][$name]
        : $default;
}

function update_option(string $name, mixed $value, string|bool|null $autoload = null): bool
{
    $GLOBALS['el_test_options'][$name] = $value;
    return true;
}

function absint(mixed $maybeint): int
{
    return abs((int) $maybeint);
}

function sanitize_hex_color(string $color): ?string
{
    $color = trim($color);
    if ($color === '') {
        return '';
    }
    return preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color) ? $color : null;
}

function sanitize_text_field(string $str): string
{
    return trim(strip_tags($str));
}

function sanitize_key(string $key): string
{
    return (string) preg_replace('/[^a-z0-9_\-]/', '', strtolower($key));
}

function wp_unslash(string|array $value): string|array
{
    return is_array($value) ? array_map('wp_unslash', $value) : stripslashes($value);
}

function wp_parse_url(string $url, int $component = -1): mixed
{
    return parse_url($url, $component);
}

function wp_strip_all_tags(string $text, bool $remove_breaks = false): string
{
    $text = (string) preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $text);
    $text = strip_tags($text);
    return $remove_breaks ? trim((string) preg_replace('/[\r\n\t ]+/', ' ', $text)) : trim($text);
}

function __(string $text, string $domain = 'default'): string
{
    return $text;
}

function esc_html__(string $text, string $domain = 'default'): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function esc_attr__(string $text, string $domain = 'default'): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function esc_html_e(string $text, string $domain = 'default'): void
{
    echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function esc_attr_e(string $text, string $domain = 'default'): void
{
    echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function esc_html(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function esc_attr(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function esc_url(string $url): string
{
    return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
}

function esc_url_raw(string $url): string
{
    return filter_var($url, FILTER_SANITIZE_URL) ?: '';
}

function esc_textarea(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function home_url(string $path = ''): string
{
    return 'https://example.test/' . ltrim($path, '/');
}

function admin_url(string $path = ''): string
{
    return 'https://example.test/wp-admin/' . ltrim($path, '/');
}

function wp_login_url(): string
{
    return 'https://example.test/wp-login.php';
}

function get_bloginfo(string $show = '', string $filter = 'raw'): string
{
    return $show === 'name' ? 'Example Site' : 'Example';
}

function get_theme_mod(string $name, mixed $default = false): mixed
{
    return $GLOBALS['el_test_theme_mods'][$name] ?? $default;
}

function wp_get_attachment_image_url(int $attachmentId, string|array $size = 'thumbnail'): string|false
{
    $GLOBALS['el_test_attachment_sizes'][$attachmentId] = $size;
    return $GLOBALS['el_test_attachments'][$attachmentId] ?? false;
}

function wp_enqueue_style(string $handle, string $src = '', array $deps = [], string|bool|null $ver = false): void
{
    $GLOBALS['el_test_enqueued_styles'][$handle] = $src;
}

function wp_add_inline_style(string $handle, string $data): bool
{
    $GLOBALS['el_test_inline_styles'][$handle] = $data;
    return true;
}

function current_user_can(string $capability): bool
{
    return $GLOBALS['el_test_can_manage'];
}

function wp_json_encode(mixed $data): string|false
{
    return json_encode($data);
}

function checked(mixed $checked, mixed $current = true, bool $display = true): string
{
    $result = (string) $checked === (string) $current ? " checked='checked'" : '';
    if ($display) {
        echo $result;
    }
    return $result;
}

function settings_fields(string $optionGroup): void
{
    echo '<input type="hidden" name="option_page" value="' . esc_attr($optionGroup) . '">';
}

function submit_button(string $text = 'Save Changes', string $type = 'primary', string $name = 'submit', bool $wrap = true): void
{
    echo '<button type="submit">' . esc_html($text) . '</button>';
}

function wp_nonce_field(string|int $action = -1, string $name = '_wpnonce', bool $referer = true, bool $display = true): string
{
    $field = '<input type="hidden" name="' . esc_attr($name) . '" value="testnonce">';
    if ($display) {
        echo $field;
    }
    return $field;
}

require_once dirname(__DIR__) . '/includes/Options.php';
require_once dirname(__DIR__) . '/includes/AdminPage.php';
require_once dirname(__DIR__) . '/includes/LoginRenderer.php';
