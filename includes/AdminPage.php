<?php

declare(strict_types=1);

namespace EstroLogin;

if (!defined('ABSPATH')) {
    exit;
}

final class AdminPage
{
    private const PAGE_SLUG = 'estro-login';
    private const OPTION_GROUP = 'estro_login_settings_group';
    private const RESET_ACTION = 'estro_login_reset';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addSettingsPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_post_' . self::RESET_ACTION, [$this, 'handleReset']);
        add_filter('plugin_action_links_' . plugin_basename(ESTRO_LOGIN_FILE), [$this, 'addActionLink']);
    }

    /**
     * @param array<int, string> $links
     * @return array<int, string>
     */
    public function addActionLink(array $links): array
    {
        $url = admin_url('options-general.php?page=' . self::PAGE_SLUG);
        $settingsLink = sprintf(
            '<a href="%s">%s</a>',
            esc_url($url),
            esc_html__('Settings', 'estro-login')
        );

        array_unshift($links, $settingsLink);

        return $links;
    }

    public function addSettingsPage(): void
    {
        add_options_page(
            __('Estro Login', 'estro-login'),
            __('Estro Login', 'estro-login'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'renderPage']
        );
    }

    public function registerSettings(): void
    {
        register_setting(
            self::OPTION_GROUP,
            ESTRO_LOGIN_OPTION,
            [
                'type'              => 'array',
                'sanitize_callback' => [Options::class, 'sanitize'],
                'default'           => Options::defaults(),
            ]
        );
    }

    public function handleReset(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Permission denied.', 'estro-login'), '', ['response' => 403]);
        }

        check_admin_referer(self::RESET_ACTION);

        update_option(ESTRO_LOGIN_OPTION, Options::defaults(), false);

        $redirectUrl = add_query_arg(
            'reset',
            '1',
            admin_url('options-general.php?page=' . self::PAGE_SLUG)
        );

        wp_safe_redirect($redirectUrl);
        exit;
    }

    public function enqueueAssets(string $hook): void
    {
        if ($hook !== 'settings_page_' . self::PAGE_SLUG) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style('estro-login-admin', ESTRO_LOGIN_URL . 'assets/admin.css', [], ESTRO_LOGIN_VERSION);
        wp_enqueue_script(
            'estro-login-admin',
            ESTRO_LOGIN_URL . 'assets/admin.js',
            [],
            ESTRO_LOGIN_VERSION,
            [
                'in_footer' => true,
                'strategy'  => 'defer',
            ]
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function presets(): array
    {
        return [
            'slate-light' => [
                'label'         => __('Slate Light', 'estro-login'),
                'layout'        => 'centered',
                'bg_color'      => '#f1f5f9',
                'bg_color_end'  => '',
                'card_bg_color' => '#ffffff',
                'primary_color' => '#0f172a',
                'card_radius'   => 8,
            ],
            'midnight-dark' => [
                'label'         => __('Midnight Dark', 'estro-login'),
                'layout'        => 'centered',
                'bg_color'      => '#090d16',
                'bg_color_end'  => '#1e293b',
                'card_bg_color' => '#111827',
                'primary_color' => '#38bdf8',
                'card_radius'   => 16,
            ],
            'split-studio' => [
                'label'         => __('Split Studio', 'estro-login'),
                'layout'        => 'split',
                'bg_color'      => '#1e1b4b',
                'bg_color_end'  => '#312e81',
                'card_bg_color' => '#ffffff',
                'primary_color' => '#4f46e5',
                'card_radius'   => 8,
            ],
            'warm-minimal' => [
                'label'         => __('Warm Minimal', 'estro-login'),
                'layout'        => 'minimal',
                'bg_color'      => '#fafaf9',
                'bg_color_end'  => '',
                'card_bg_color' => '#ffffff',
                'primary_color' => '#c2410c',
                'card_radius'   => 8,
            ],
        ];
    }

    public function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $options = Options::get();
        $defaults = Options::defaults();

        $logoUrl = $options['logo_id'] > 0 ? (wp_get_attachment_image_url((int) $options['logo_id'], 'medium') ?: '') : '';
        $fallbackLogoId = absint(get_theme_mod('custom_logo'));
        $fallbackLogoUrl = $fallbackLogoId > 0 ? (wp_get_attachment_image_url($fallbackLogoId, 'medium') ?: '') : '';
        $effectiveLogoUrl = $logoUrl !== '' ? $logoUrl : $fallbackLogoUrl;

        $bgImageUrl = $options['bg_image_id'] > 0 ? (wp_get_attachment_image_url((int) $options['bg_image_id'], 'large') ?: '') : '';
        $siteName = get_bloginfo('name');
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only status flag after nonce-verified redirect.
        $isReset = isset($_GET['reset']) && sanitize_key((string) wp_unslash($_GET['reset'])) === '1';

        $layoutLabels = [
            'centered' => __('Centered', 'estro-login'),
            'split'    => __('Split', 'estro-login'),
            'minimal'  => __('Minimal', 'estro-login'),
        ];
        ?>
        <div class="wrap el-admin">
            <div class="el-topbar">
                <div>
                    <h1><?php esc_html_e('Estro Login', 'estro-login'); ?></h1>
                    <p class="el-subtitle"><?php esc_html_e('Customize layout, branding, background, and security for wp-login.php in real time.', 'estro-login'); ?></p>
                </div>
                <a class="el-btn-secondary" href="<?php echo esc_url(wp_login_url()); ?>" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e('Open live login', 'estro-login'); ?> &#8599;
                </a>
            </div>

            <?php if ($isReset) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Settings restored to defaults.', 'estro-login'); ?></p>
                </div>
            <?php endif; ?>

            <div class="el-grid">
                <div class="el-controls">
                    <form id="estro-login-form" action="<?php echo esc_url(admin_url('options.php')); ?>" method="post">
                        <?php settings_fields(self::OPTION_GROUP); ?>

                        <section class="el-card">
                            <h2><?php esc_html_e('Quick Presets', 'estro-login'); ?></h2>
                            <div class="el-presets" data-presets="<?php echo esc_attr((string) wp_json_encode(self::presets())); ?>">
                                <?php foreach (self::presets() as $presetSlug => $preset) : ?>
                                    <button type="button" class="el-preset-btn" data-preset="<?php echo esc_attr($presetSlug); ?>">
                                        <span class="el-preset-swatch" style="--swatch-bg: <?php echo esc_attr((string) $preset['bg_color']); ?>; --swatch-accent: <?php echo esc_attr((string) $preset['primary_color']); ?>;" aria-hidden="true"></span>
                                        <span><?php echo esc_html((string) $preset['label']); ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="el-card">
                            <h2><?php esc_html_e('Layout & Branding', 'estro-login'); ?></h2>

                            <fieldset class="el-field el-fieldset">
                                <legend class="el-label"><?php esc_html_e('Page layout', 'estro-login'); ?></legend>
                                <div class="el-layouts">
                                    <?php foreach ($layoutLabels as $slug => $label) : ?>
                                        <label class="el-layout-option">
                                            <input
                                                type="radio"
                                                name="<?php echo esc_attr(ESTRO_LOGIN_OPTION); ?>[layout]"
                                                value="<?php echo esc_attr($slug); ?>"
                                                <?php checked($options['layout'], $slug); ?>
                                            >
                                            <span class="el-layout-box el-layout-box--<?php echo esc_attr($slug); ?>" aria-hidden="true"></span>
                                            <span><?php echo esc_html($label); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>

                            <div class="el-field">
                                <span class="el-label"><?php esc_html_e('Logo', 'estro-login'); ?></span>
                                <div class="el-media" data-media-picker="logo" data-media-title="<?php esc_attr_e('Select logo', 'estro-login'); ?>" data-media-button="<?php esc_attr_e('Use image', 'estro-login'); ?>">
                                    <input
                                        type="hidden"
                                        id="el_logo_id"
                                        name="<?php echo esc_attr(ESTRO_LOGIN_OPTION); ?>[logo_id]"
                                        value="<?php echo esc_attr((string) $options['logo_id']); ?>"
                                    >
                                    <div class="el-media__preview <?php echo $logoUrl === '' ? 'is-empty' : ''; ?>">
                                        <img src="<?php echo esc_url($logoUrl); ?>" alt="">
                                    </div>
                                    <div class="el-media__actions">
                                        <div class="el-media__btn-row">
                                            <button type="button" class="el-btn-secondary" data-media-select><?php esc_html_e('Select logo', 'estro-login'); ?></button>
                                            <button type="button" class="el-btn-danger" data-media-remove aria-label="<?php esc_attr_e('Remove logo', 'estro-login'); ?>" <?php echo $logoUrl === '' ? 'hidden' : ''; ?>>
                                                <?php esc_html_e('Remove', 'estro-login'); ?>
                                            </button>
                                        </div>
                                        <p class="description"><?php esc_html_e('When empty, automatically falls back to the Site Custom Logo.', 'estro-login'); ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="el-field">
                                <label class="el-label" for="el_logo_height">
                                    <span><?php esc_html_e('Logo height', 'estro-login'); ?></span>
                                    <strong id="el_logo_height_val" class="el-range-badge"><?php echo esc_html((string) $options['logo_height']); ?>px</strong>
                                </label>
                                <input
                                    type="range"
                                    id="el_logo_height"
                                    name="<?php echo esc_attr(ESTRO_LOGIN_OPTION); ?>[logo_height]"
                                    min="24"
                                    max="160"
                                    step="2"
                                    value="<?php echo esc_attr((string) $options['logo_height']); ?>"
                                >
                            </div>
                        </section>

                        <section class="el-card">
                            <h2><?php esc_html_e('Colors & Background', 'estro-login'); ?></h2>

                            <div class="el-colors-grid">
                                <?php $this->renderColorInput('bg_color', __('Background', 'estro-login'), (string) $options['bg_color'], (string) $defaults['bg_color']); ?>
                                <?php $this->renderColorInput('bg_color_end', __('Gradient end (optional)', 'estro-login'), (string) $options['bg_color_end'], (string) $options['bg_color'], true); ?>
                                <?php $this->renderColorInput('card_bg_color', __('Card background', 'estro-login'), (string) $options['card_bg_color'], (string) $defaults['card_bg_color']); ?>
                                <?php $this->renderColorInput('primary_color', __('Primary color', 'estro-login'), (string) $options['primary_color'], (string) $defaults['primary_color']); ?>
                            </div>

                            <div class="el-field">
                                <span class="el-label"><?php esc_html_e('Background image', 'estro-login'); ?></span>
                                <div class="el-media" data-media-picker="bg" data-media-title="<?php esc_attr_e('Select background image', 'estro-login'); ?>" data-media-button="<?php esc_attr_e('Use image', 'estro-login'); ?>">
                                    <input
                                        type="hidden"
                                        id="el_bg_image_id"
                                        name="<?php echo esc_attr(ESTRO_LOGIN_OPTION); ?>[bg_image_id]"
                                        value="<?php echo esc_attr((string) $options['bg_image_id']); ?>"
                                    >
                                    <div class="el-media__preview el-media__preview--bg <?php echo $bgImageUrl === '' ? 'is-empty' : ''; ?>">
                                        <img src="<?php echo esc_url($bgImageUrl); ?>" alt="">
                                    </div>
                                    <div class="el-media__actions">
                                        <div class="el-media__btn-row">
                                            <button type="button" class="el-btn-secondary" data-media-select aria-label="<?php esc_attr_e('Select background image', 'estro-login'); ?>"><?php esc_html_e('Select image', 'estro-login'); ?></button>
                                            <button type="button" class="el-btn-danger" data-media-remove aria-label="<?php esc_attr_e('Remove background image', 'estro-login'); ?>" <?php echo $bgImageUrl === '' ? 'hidden' : ''; ?>>
                                                <?php esc_html_e('Remove', 'estro-login'); ?>
                                            </button>
                                        </div>
                                        <p class="description"><?php esc_html_e('In Split layout, the image fills the side panel.', 'estro-login'); ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="el-field <?php echo $bgImageUrl === '' ? 'is-muted' : ''; ?>" data-overlay-field>
                                <label class="el-label" for="el_bg_overlay">
                                    <span><?php esc_html_e('Background overlay', 'estro-login'); ?></span>
                                    <strong id="el_bg_overlay_val" class="el-range-badge"><?php echo esc_html((string) $options['bg_overlay']); ?>%</strong>
                                </label>
                                <input
                                    type="range"
                                    id="el_bg_overlay"
                                    name="<?php echo esc_attr(ESTRO_LOGIN_OPTION); ?>[bg_overlay]"
                                    min="0"
                                    max="90"
                                    step="5"
                                    value="<?php echo esc_attr((string) $options['bg_overlay']); ?>"
                                >
                            </div>

                            <fieldset class="el-field el-fieldset">
                                <legend class="el-label"><?php esc_html_e('Form border radius', 'estro-login'); ?></legend>
                                <div class="el-pills">
                                    <?php
                                    $radiiLabels = [
                                        0  => __('Sharp (0px)', 'estro-login'),
                                        8  => __('Standard (8px)', 'estro-login'),
                                        16 => __('Rounded (16px)', 'estro-login'),
                                    ];
                                    foreach ($radiiLabels as $radius => $label) :
                                        ?>
                                        <label class="el-pill">
                                            <input
                                                type="radio"
                                                name="<?php echo esc_attr(ESTRO_LOGIN_OPTION); ?>[card_radius]"
                                                value="<?php echo esc_attr((string) $radius); ?>"
                                                <?php checked((int) $options['card_radius'], $radius); ?>
                                            >
                                            <span><?php echo esc_html($label); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>
                        </section>

                        <section class="el-card">
                            <h2><?php esc_html_e('UX & Security', 'estro-login'); ?></h2>

                            <div class="el-field">
                                <label class="el-label" for="el_welcome_text"><?php esc_html_e('Welcome message (optional)', 'estro-login'); ?></label>
                                <input
                                    type="text"
                                    id="el_welcome_text"
                                    class="el-input"
                                    name="<?php echo esc_attr(ESTRO_LOGIN_OPTION); ?>[welcome_text]"
                                    value="<?php echo esc_attr((string) $options['welcome_text']); ?>"
                                    placeholder="<?php esc_attr_e('e.g. Sign in to manage your site.', 'estro-login'); ?>"
                                    maxlength="160"
                                >
                            </div>

                            <div class="el-field">
                                <label class="el-label" for="el_footer_text"><?php esc_html_e('Footer note (optional)', 'estro-login'); ?></label>
                                <input
                                    type="text"
                                    id="el_footer_text"
                                    class="el-input"
                                    name="<?php echo esc_attr(ESTRO_LOGIN_OPTION); ?>[footer_text]"
                                    value="<?php echo esc_attr((string) $options['footer_text']); ?>"
                                    placeholder="<?php esc_attr_e('e.g. Need help? support@example.com', 'estro-login'); ?>"
                                    maxlength="160"
                                >
                            </div>

                            <div class="el-toggles">
                                <label class="el-switch">
                                    <input
                                        type="checkbox"
                                        name="<?php echo esc_attr(ESTRO_LOGIN_OPTION); ?>[hide_language_switcher]"
                                        value="1"
                                        <?php checked((bool) $options['hide_language_switcher']); ?>
                                    >
                                    <span class="el-switch__track" aria-hidden="true"></span>
                                    <span><?php esc_html_e('Hide language switcher', 'estro-login'); ?></span>
                                </label>

                                <label class="el-switch">
                                    <input
                                        type="checkbox"
                                        name="<?php echo esc_attr(ESTRO_LOGIN_OPTION); ?>[hide_back_to_blog]"
                                        value="1"
                                        <?php checked((bool) $options['hide_back_to_blog']); ?>
                                    >
                                    <span class="el-switch__track" aria-hidden="true"></span>
                                    <span><?php esc_html_e('Hide "Back to site" link', 'estro-login'); ?></span>
                                </label>

                                <label class="el-switch">
                                    <input
                                        type="checkbox"
                                        name="<?php echo esc_attr(ESTRO_LOGIN_OPTION); ?>[generic_login_errors]"
                                        value="1"
                                        <?php checked((bool) $options['generic_login_errors']); ?>
                                    >
                                    <span class="el-switch__track" aria-hidden="true"></span>
                                    <span><?php esc_html_e('Generic login error message (prevents username enumeration)', 'estro-login'); ?></span>
                                </label>
                            </div>
                        </section>

                        <section class="el-card">
                            <h2><?php esc_html_e('Custom CSS', 'estro-login'); ?></h2>
                            <div class="el-field">
                                <label class="screen-reader-text" for="el_custom_css"><?php esc_html_e('Custom CSS', 'estro-login'); ?></label>
                                <textarea
                                    id="el_custom_css"
                                    name="<?php echo esc_attr(ESTRO_LOGIN_OPTION); ?>[custom_css]"
                                    rows="4"
                                    class="el-code"
                                    placeholder=".estro-login #login form { ... }"
                                ><?php echo esc_textarea((string) $options['custom_css']); ?></textarea>
                            </div>
                        </section>

                        <section
                            class="el-card"
                            data-transfer
                            data-version="<?php echo esc_attr(ESTRO_LOGIN_VERSION); ?>"
                            data-msg-exported="<?php esc_attr_e('Settings exported to JSON.', 'estro-login'); ?>"
                            data-msg-imported="<?php esc_attr_e('Settings loaded into preview. Click "Save changes" to apply.', 'estro-login'); ?>"
                            data-msg-invalid="<?php esc_attr_e('Invalid Estro Login JSON file.', 'estro-login'); ?>"
                        >
                            <h2><?php esc_html_e('Export & Import', 'estro-login'); ?></h2>
                            <p class="el-transfer-desc"><?php esc_html_e('Transfer layout, colors, text, toggles, and Custom CSS between sites. Logos and background images stay site-specific.', 'estro-login'); ?></p>
                            <div class="el-transfer-row">
                                <button type="button" class="el-btn-secondary" data-transfer-export>
                                    <?php esc_html_e('Export JSON', 'estro-login'); ?>
                                </button>
                                <button type="button" class="el-btn-secondary" data-transfer-import>
                                    <?php esc_html_e('Import JSON', 'estro-login'); ?>
                                </button>
                                <input type="file" accept="application/json,.json" data-transfer-file hidden>
                            </div>
                            <p class="el-transfer-status" data-transfer-status role="status" aria-live="polite" hidden></p>
                        </section>

                        <div class="el-actions">
                            <?php submit_button(__('Save changes', 'estro-login'), 'primary', 'submit', false); ?>
                            <button
                                type="submit"
                                form="estro-login-reset-form"
                                class="el-btn-danger"
                                data-confirm-reset="<?php esc_attr_e('Restore all login settings to defaults?', 'estro-login'); ?>"
                            >
                                <?php esc_html_e('Reset to defaults', 'estro-login'); ?>
                            </button>
                        </div>
                    </form>

                    <form id="estro-login-reset-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                        <input type="hidden" name="action" value="<?php echo esc_attr(self::RESET_ACTION); ?>">
                        <?php wp_nonce_field(self::RESET_ACTION); ?>
                    </form>
                </div>

                <aside class="el-preview-col">
                    <div class="el-preview-wrap">
                        <div class="el-preview-head">
                            <div class="el-preview-head__meta">
                                <strong><?php esc_html_e('Live preview', 'estro-login'); ?></strong>
                                <span id="el_preview_layout_badge" class="el-preview-badge"><?php echo esc_html($layoutLabels[(string) $options['layout']] ?? (string) $options['layout']); ?></span>
                            </div>

                            <div class="el-preview-modes" role="group" aria-label="<?php esc_attr_e('Preview viewport', 'estro-login'); ?>">
                                <button type="button" class="el-preview-mode" data-preview-mode="desktop" aria-pressed="true">
                                    <?php esc_html_e('Desktop', 'estro-login'); ?>
                                </button>
                                <button type="button" class="el-preview-mode" data-preview-mode="mobile" aria-pressed="false">
                                    <?php esc_html_e('Mobile', 'estro-login'); ?>
                                </button>
                            </div>
                        </div>

                        <div id="el_preview_viewport" class="el-preview-viewport">
                            <div
                                id="estro-login-preview"
                                class="el-preview el-preview--<?php echo esc_attr((string) $options['layout']); ?> <?php echo $bgImageUrl !== '' ? 'is-has-bg-image' : ''; ?>"
                                data-fallback-logo="<?php echo esc_url($fallbackLogoUrl); ?>"
                                data-site-name="<?php echo esc_attr($siteName); ?>"
                                data-layout-labels="<?php echo esc_attr((string) wp_json_encode($layoutLabels)); ?>"
                                aria-hidden="true"
                            >
                                <div class="el-preview__aside"></div>
                                <div class="el-preview__main">
                                    <div class="el-preview__stage">
                                        <div class="el-preview__logo <?php echo $effectiveLogoUrl === '' ? 'is-fallback' : ''; ?>">
                                            <img src="<?php echo esc_url($effectiveLogoUrl); ?>" alt="">
                                            <span class="el-preview__site-name"><?php echo esc_html($siteName); ?></span>
                                        </div>

                                        <p class="el-preview__welcome" <?php echo $options['welcome_text'] === '' ? 'hidden' : ''; ?>>
                                            <?php echo esc_html((string) $options['welcome_text']); ?>
                                        </p>

                                        <div class="el-preview__card">
                                            <div class="el-preview__field">
                                                <span><?php esc_html_e('Username or Email Address', 'estro-login'); ?></span>
                                                <div class="el-preview__input"></div>
                                            </div>
                                            <div class="el-preview__field">
                                                <span><?php esc_html_e('Password', 'estro-login'); ?></span>
                                                <div class="el-preview__input"></div>
                                            </div>
                                            <div class="el-preview__btn"><?php esc_html_e('Log In', 'estro-login'); ?></div>
                                        </div>

                                        <div class="el-preview__links">
                                            <span><?php esc_html_e('Lost your password?', 'estro-login'); ?></span>
                                            <span class="el-preview__back" <?php echo !empty($options['hide_back_to_blog']) ? 'hidden' : ''; ?>>
                                                &larr; <?php echo esc_html($siteName); ?>
                                            </span>
                                        </div>

                                        <p class="el-preview__footer" <?php echo $options['footer_text'] === '' ? 'hidden' : ''; ?>>
                                            <?php echo esc_html((string) $options['footer_text']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
        <?php
    }

    private function renderColorInput(string $key, string $label, string $value, string $pickerFallback, bool $isOptional = false): void
    {
        $pickerValue = $value !== '' ? $value : $pickerFallback;
        ?>
        <div class="el-color-control" data-color-control data-optional="<?php echo $isOptional ? '1' : '0'; ?>">
            <label class="el-label" for="el_<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label>
            <div class="el-color-row">
                <input
                    type="color"
                    value="<?php echo esc_attr($pickerValue); ?>"
                    aria-label="<?php echo esc_attr(sprintf(__('%s color picker', 'estro-login'), $label)); ?>"
                    data-color-swatch
                >
                <input
                    type="text"
                    id="el_<?php echo esc_attr($key); ?>"
                    name="<?php echo esc_attr(ESTRO_LOGIN_OPTION . '[' . $key . ']'); ?>"
                    value="<?php echo esc_attr($value); ?>"
                    placeholder="<?php echo $isOptional ? esc_attr__('None', 'estro-login') : ''; ?>"
                    aria-label="<?php echo esc_attr(sprintf(__('%s hex value', 'estro-login'), $label)); ?>"
                    maxlength="7"
                    data-color-hex
                >
                <?php if ($isOptional) : ?>
                    <button
                        type="button"
                        class="el-btn-ghost"
                        aria-label="<?php echo esc_attr(sprintf(__('Clear %s', 'estro-login'), $label)); ?>"
                        data-color-clear
                    ><?php esc_html_e('Clear', 'estro-login'); ?></button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
