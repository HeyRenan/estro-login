<?php
/**
 * Plugin Name:       Estro Login
 * Plugin URI:        https://github.com/HeyRenan/estro-login
 * Description:       Lightweight, zero-dependency WordPress login customizer with live preview, 3 layouts, WCAG auto-contrast, and security hygiene.
 * Version:           1.2.0
 * Requires at least: 6.7
 * Requires PHP:      8.1
 * Author:            Renan Cordeiro
 * Author URI:        https://github.com/HeyRenan
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       estro-login
 * Domain Path:       /languages
 * Update URI:        false
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('ESTRO_LOGIN_VERSION', '1.2.0');
define('ESTRO_LOGIN_FILE', __FILE__);
define('ESTRO_LOGIN_DIR', plugin_dir_path(__FILE__));
define('ESTRO_LOGIN_URL', plugin_dir_url(__FILE__));
define('ESTRO_LOGIN_OPTION', 'estro_login_settings');

require_once ESTRO_LOGIN_DIR . 'includes/Options.php';
require_once ESTRO_LOGIN_DIR . 'includes/AdminPage.php';
require_once ESTRO_LOGIN_DIR . 'includes/LoginRenderer.php';

register_activation_hook(__FILE__, static function (): void {
    if (get_option(ESTRO_LOGIN_OPTION, null) === null) {
        add_option(ESTRO_LOGIN_OPTION, EstroLogin\Options::defaults(), '', false);
    }
});

add_action('init', static function (): void {
    if (!is_textdomain_loaded('estro-login')) {
        $locale = determine_locale();
        $l10nFile = ESTRO_LOGIN_DIR . 'languages/estro-login-' . $locale . '.l10n.php';
        if (is_readable($l10nFile)) {
            load_textdomain('estro-login', $l10nFile, $locale);
        }
    }
});

(new EstroLogin\AdminPage())->register();
(new EstroLogin\LoginRenderer())->register();
