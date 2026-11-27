<?php

declare(strict_types=1);

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('estro_login_settings');
