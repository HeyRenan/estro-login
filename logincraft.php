<?php
/**
 * Plugin Name:       LoginCraft
 * Plugin URI:        https://github.com/HeyRenan/logincraft
 * Description:       Lightweight, zero-dependency WordPress login customizer with live preview, 3 layouts, WCAG auto-contrast, and security hygiene.
 * Version:           1.1.0
 * Requires at least: 6.7
 * Requires PHP:      8.1
 * Author:            Renan Cordeiro
 * Author URI:        https://github.com/HeyRenan
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       logincraft
 * Domain Path:       /languages
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('LOGINCRAFT_VERSION', '1.1.0');
define('LOGINCRAFT_FILE', __FILE__);
define('LOGINCRAFT_DIR', plugin_dir_path(__FILE__));
define('LOGINCRAFT_URL', plugin_dir_url(__FILE__));
define('LOGINCRAFT_OPTION', 'logincraft_settings');

require_once LOGINCRAFT_DIR . 'includes/Options.php';
require_once LOGINCRAFT_DIR . 'includes/AdminPage.php';
require_once LOGINCRAFT_DIR . 'includes/LoginRenderer.php';

register_activation_hook(__FILE__, static function (): void {
    if (get_option(LOGINCRAFT_OPTION, null) === null) {
        add_option(LOGINCRAFT_OPTION, LoginCraft\Options::defaults(), '', false);
    }
});

add_action('init', static function (): void {
    if (!is_textdomain_loaded('logincraft')) {
        $locale = determine_locale();
        $l10nFile = LOGINCRAFT_DIR . 'languages/logincraft-' . $locale . '.l10n.php';
        if (is_readable($l10nFile)) {
            load_textdomain('logincraft', $l10nFile, $locale);
        }
    }
});

(new LoginCraft\AdminPage())->register();
(new LoginCraft\LoginRenderer())->register();
