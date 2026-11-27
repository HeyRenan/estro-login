<?php

declare(strict_types=1);

namespace LoginCraft;

if (!defined('ABSPATH')) {
    exit;
}

final class AdminPage
{
    private const PAGE_SLUG = 'logincraft';
    private const OPTION_GROUP = 'logincraft_settings_group';
    private const RESET_ACTION = 'logincraft_reset';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addSettingsPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_post_' . self::RESET_ACTION, [$this, 'handleReset']);
        add_filter('plugin_action_links_' . plugin_basename(LOGINCRAFT_FILE), [$this, 'addActionLink']);
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
            esc_html__('Settings', 'logincraft')
        );

        array_unshift($links, $settingsLink);

        return $links;
    }

    public function addSettingsPage(): void
    {
        add_options_page(
            __('LoginCraft', 'logincraft'),
            __('LoginCraft', 'logincraft'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'renderPage']
        );
    }

    public function registerSettings(): void
    {
        register_setting(
            self::OPTION_GROUP,
            LOGINCRAFT_OPTION,
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
            wp_die(esc_html__('Permission denied.', 'logincraft'), '', ['response' => 403]);
        }

        check_admin_referer(self::RESET_ACTION);

        update_option(LOGINCRAFT_OPTION, Options::defaults(), false);

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
        wp_enqueue_style('logincraft-admin', LOGINCRAFT_URL . 'assets/admin.css', [], LOGINCRAFT_VERSION);
        wp_enqueue_script(
            'logincraft-admin',
            LOGINCRAFT_URL . 'assets/admin.js',
            [],
            LOGINCRAFT_VERSION,
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
                'label'         => __('Slate Light', 'logincraft'),
                'layout'        => 'centered',
                'bg_color'      => '#f1f5f9',
                'bg_color_end'  => '',
                'card_bg_color' => '#ffffff',
                'primary_color' => '#0f172a',
                'card_radius'   => 8,
            ],
            'midnight-dark' => [
                'label'         => __('Midnight Dark', 'logincraft'),
                'layout'        => 'centered',
                'bg_color'      => '#090d16',
                'bg_color_end'  => '#1e293b',
                'card_bg_color' => '#111827',
                'primary_color' => '#38bdf8',
                'card_radius'   => 16,
            ],
            'split-studio' => [
                'label'         => __('Split Studio', 'logincraft'),
                'layout'        => 'split',
                'bg_color'      => '#1e1b4b',
                'bg_color_end'  => '#312e81',
                'card_bg_color' => '#ffffff',
                'primary_color' => '#4f46e5',
                'card_radius'   => 8,
            ],
            'warm-minimal' => [
                'label'         => __('Warm Minimal', 'logincraft'),
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
            'centered' => __('Centered', 'logincraft'),
            'split'    => __('Split', 'logincraft'),
            'minimal'  => __('Minimal', 'logincraft'),
        ];
        ?>
        <div class="wrap lc-admin">
            <div class="lc-topbar">
                <div>
                    <h1><?php esc_html_e('LoginCraft', 'logincraft'); ?></h1>
                    <p class="lc-subtitle"><?php esc_html_e('Customize layout, branding, background, and security for wp-login.php in real time.', 'logincraft'); ?></p>
                </div>
                <a class="button button-secondary" href="<?php echo esc_url(wp_login_url()); ?>" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e('Open live login', 'logincraft'); ?> &#8599;
                </a>
            </div>

            <?php if ($isReset) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Settings restored to defaults.', 'logincraft'); ?></p>
                </div>
            <?php endif; ?>

            <div class="lc-grid">
                <div class="lc-controls">
                    <form id="logincraft-form" action="<?php echo esc_url(admin_url('options.php')); ?>" method="post">
                        <?php settings_fields(self::OPTION_GROUP); ?>

                        <section class="lc-card">
                            <h2><?php esc_html_e('Quick Presets', 'logincraft'); ?></h2>
                            <div class="lc-presets" data-presets="<?php echo esc_attr((string) wp_json_encode(self::presets())); ?>">
                                <?php foreach (self::presets() as $presetSlug => $preset) : ?>
                                    <button type="button" class="button lc-preset-btn" data-preset="<?php echo esc_attr($presetSlug); ?>">
                                        <span class="lc-preset-swatch" style="--swatch-bg: <?php echo esc_attr((string) $preset['bg_color']); ?>; --swatch-accent: <?php echo esc_attr((string) $preset['primary_color']); ?>;" aria-hidden="true"></span>
                                        <span><?php echo esc_html((string) $preset['label']); ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="lc-card">
                            <h2><?php esc_html_e('1. Layout & Branding', 'logincraft'); ?></h2>

                            <fieldset class="lc-field lc-fieldset">
                                <legend class="lc-label"><?php esc_html_e('Page layout', 'logincraft'); ?></legend>
                                <div class="lc-layouts">
                                    <?php foreach ($layoutLabels as $slug => $label) : ?>
                                        <label class="lc-layout-option">
                                            <input
                                                type="radio"
                                                name="<?php echo esc_attr(LOGINCRAFT_OPTION); ?>[layout]"
                                                value="<?php echo esc_attr($slug); ?>"
                                                <?php checked($options['layout'], $slug); ?>
                                            >
                                            <span class="lc-layout-box lc-layout-box--<?php echo esc_attr($slug); ?>" aria-hidden="true"></span>
                                            <span><?php echo esc_html($label); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>

                            <div class="lc-field">
                                <span class="lc-label"><?php esc_html_e('Logo', 'logincraft'); ?></span>
                                <div class="lc-media" data-media-picker="logo" data-media-title="<?php esc_attr_e('Select logo', 'logincraft'); ?>" data-media-button="<?php esc_attr_e('Use image', 'logincraft'); ?>">
                                    <input
                                        type="hidden"
                                        id="lc_logo_id"
                                        name="<?php echo esc_attr(LOGINCRAFT_OPTION); ?>[logo_id]"
                                        value="<?php echo esc_attr((string) $options['logo_id']); ?>"
                                    >
                                    <div class="lc-media__preview <?php echo $logoUrl === '' ? 'is-empty' : ''; ?>">
                                        <img src="<?php echo esc_url($logoUrl); ?>" alt="">
                                    </div>
                                    <div class="lc-media__actions">
                                        <button type="button" class="button" data-media-select><?php esc_html_e('Select logo', 'logincraft'); ?></button>
                                        <button type="button" class="button-link-delete" data-media-remove aria-label="<?php esc_attr_e('Remove logo', 'logincraft'); ?>" <?php echo $logoUrl === '' ? 'hidden' : ''; ?>>
                                            <?php esc_html_e('Remove', 'logincraft'); ?>
                                        </button>
                                        <p class="description"><?php esc_html_e('When empty, automatically falls back to the Site Custom Logo.', 'logincraft'); ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="lc-field">
                                <label class="lc-label" for="lc_logo_height">
                                    <span><?php esc_html_e('Logo height', 'logincraft'); ?></span>
                                    <strong id="lc_logo_height_val"><?php echo esc_html((string) $options['logo_height']); ?>px</strong>
                                </label>
                                <input
                                    type="range"
                                    id="lc_logo_height"
                                    name="<?php echo esc_attr(LOGINCRAFT_OPTION); ?>[logo_height]"
                                    min="24"
                                    max="160"
                                    step="2"
                                    value="<?php echo esc_attr((string) $options['logo_height']); ?>"
                                >
                            </div>
                        </section>

                        <section class="lc-card">
                            <h2><?php esc_html_e('2. Colors & Background', 'logincraft'); ?></h2>

                            <div class="lc-colors-grid">
                                <?php $this->renderColorInput('bg_color', __('Background', 'logincraft'), (string) $options['bg_color'], (string) $defaults['bg_color']); ?>
                                <?php $this->renderColorInput('bg_color_end', __('Gradient end (optional)', 'logincraft'), (string) $options['bg_color_end'], (string) $options['bg_color'], true); ?>
                                <?php $this->renderColorInput('card_bg_color', __('Card background', 'logincraft'), (string) $options['card_bg_color'], (string) $defaults['card_bg_color']); ?>
                                <?php $this->renderColorInput('primary_color', __('Primary color', 'logincraft'), (string) $options['primary_color'], (string) $defaults['primary_color']); ?>
                            </div>

                            <div class="lc-field">
                                <span class="lc-label"><?php esc_html_e('Background image', 'logincraft'); ?></span>
                                <div class="lc-media" data-media-picker="bg" data-media-title="<?php esc_attr_e('Select background image', 'logincraft'); ?>" data-media-button="<?php esc_attr_e('Use image', 'logincraft'); ?>">
                                    <input
                                        type="hidden"
                                        id="lc_bg_image_id"
                                        name="<?php echo esc_attr(LOGINCRAFT_OPTION); ?>[bg_image_id]"
                                        value="<?php echo esc_attr((string) $options['bg_image_id']); ?>"
                                    >
                                    <div class="lc-media__preview lc-media__preview--bg <?php echo $bgImageUrl === '' ? 'is-empty' : ''; ?>">
                                        <img src="<?php echo esc_url($bgImageUrl); ?>" alt="">
                                    </div>
                                    <div class="lc-media__actions">
                                        <button type="button" class="button" data-media-select aria-label="<?php esc_attr_e('Select background image', 'logincraft'); ?>"><?php esc_html_e('Select image', 'logincraft'); ?></button>
                                        <button type="button" class="button-link-delete" data-media-remove aria-label="<?php esc_attr_e('Remove background image', 'logincraft'); ?>" <?php echo $bgImageUrl === '' ? 'hidden' : ''; ?>>
                                            <?php esc_html_e('Remove', 'logincraft'); ?>
                                        </button>
                                        <p class="description"><?php esc_html_e('In Split layout, the image fills the side panel.', 'logincraft'); ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="lc-field">
                                <label class="lc-label" for="lc_bg_overlay">
                                    <span><?php esc_html_e('Background overlay', 'logincraft'); ?></span>
                                    <strong id="lc_bg_overlay_val"><?php echo esc_html((string) $options['bg_overlay']); ?>%</strong>
                                </label>
                                <input
                                    type="range"
                                    id="lc_bg_overlay"
                                    name="<?php echo esc_attr(LOGINCRAFT_OPTION); ?>[bg_overlay]"
                                    min="0"
                                    max="90"
                                    step="5"
                                    value="<?php echo esc_attr((string) $options['bg_overlay']); ?>"
                                >
                            </div>

                            <fieldset class="lc-field lc-fieldset">
                                <legend class="lc-label"><?php esc_html_e('Form border radius', 'logincraft'); ?></legend>
                                <div class="lc-pills">
                                    <?php
                                    $radiiLabels = [
                                        0  => __('Sharp (0px)', 'logincraft'),
                                        8  => __('Standard (8px)', 'logincraft'),
                                        16 => __('Rounded (16px)', 'logincraft'),
                                    ];
                                    foreach ($radiiLabels as $radius => $label) :
                                        ?>
                                        <label class="lc-pill">
                                            <input
                                                type="radio"
                                                name="<?php echo esc_attr(LOGINCRAFT_OPTION); ?>[card_radius]"
                                                value="<?php echo esc_attr((string) $radius); ?>"
                                                <?php checked((int) $options['card_radius'], $radius); ?>
                                            >
                                            <span><?php echo esc_html($label); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>
                        </section>

                        <section class="lc-card">
                            <h2><?php esc_html_e('3. UX & Security', 'logincraft'); ?></h2>

                            <div class="lc-field">
                                <label class="lc-label" for="lc_welcome_text"><?php esc_html_e('Welcome message (optional)', 'logincraft'); ?></label>
                                <input
                                    type="text"
                                    id="lc_welcome_text"
                                    class="regular-text"
                                    name="<?php echo esc_attr(LOGINCRAFT_OPTION); ?>[welcome_text]"
                                    value="<?php echo esc_attr((string) $options['welcome_text']); ?>"
                                    placeholder="<?php esc_attr_e('e.g. Sign in to manage your site.', 'logincraft'); ?>"
                                    maxlength="160"
                                >
                            </div>

                            <div class="lc-field">
                                <label class="lc-label" for="lc_footer_text"><?php esc_html_e('Footer note (optional)', 'logincraft'); ?></label>
                                <input
                                    type="text"
                                    id="lc_footer_text"
                                    class="regular-text"
                                    name="<?php echo esc_attr(LOGINCRAFT_OPTION); ?>[footer_text]"
                                    value="<?php echo esc_attr((string) $options['footer_text']); ?>"
                                    placeholder="<?php esc_attr_e('e.g. Need help? support@example.com', 'logincraft'); ?>"
                                    maxlength="160"
                                >
                            </div>

                            <div class="lc-toggles">
                                <label>
                                    <input
                                        type="checkbox"
                                        name="<?php echo esc_attr(LOGINCRAFT_OPTION); ?>[hide_language_switcher]"
                                        value="1"
                                        <?php checked((bool) $options['hide_language_switcher']); ?>
                                    >
                                    <span><?php esc_html_e('Hide language switcher', 'logincraft'); ?></span>
                                </label>

                                <label>
                                    <input
                                        type="checkbox"
                                        name="<?php echo esc_attr(LOGINCRAFT_OPTION); ?>[hide_back_to_blog]"
                                        value="1"
                                        <?php checked((bool) $options['hide_back_to_blog']); ?>
                                    >
                                    <span><?php esc_html_e('Hide "Back to site" link', 'logincraft'); ?></span>
                                </label>

                                <label>
                                    <input
                                        type="checkbox"
                                        name="<?php echo esc_attr(LOGINCRAFT_OPTION); ?>[generic_login_errors]"
                                        value="1"
                                        <?php checked((bool) $options['generic_login_errors']); ?>
                                    >
                                    <span><?php esc_html_e('Generic login error message (prevents username enumeration)', 'logincraft'); ?></span>
                                </label>
                            </div>
                        </section>

                        <section class="lc-card">
                            <h2><?php esc_html_e('4. Custom CSS', 'logincraft'); ?></h2>
                            <div class="lc-field">
                                <label class="screen-reader-text" for="lc_custom_css"><?php esc_html_e('Custom CSS', 'logincraft'); ?></label>
                                <textarea
                                    id="lc_custom_css"
                                    name="<?php echo esc_attr(LOGINCRAFT_OPTION); ?>[custom_css]"
                                    rows="4"
                                    class="lc-code"
                                    placeholder=".logincraft #login form { ... }"
                                ><?php echo esc_textarea((string) $options['custom_css']); ?></textarea>
                            </div>
                        </section>

                        <div class="lc-actions">
                            <?php submit_button(__('Save changes', 'logincraft'), 'primary', 'submit', false); ?>
                            <button
                                type="submit"
                                form="logincraft-reset-form"
                                class="button button-link-delete"
                                data-confirm-reset="<?php esc_attr_e('Restore all login settings to defaults?', 'logincraft'); ?>"
                            >
                                <?php esc_html_e('Reset to defaults', 'logincraft'); ?>
                            </button>
                        </div>
                    </form>

                    <form id="logincraft-reset-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                        <input type="hidden" name="action" value="<?php echo esc_attr(self::RESET_ACTION); ?>">
                        <?php wp_nonce_field(self::RESET_ACTION); ?>
                    </form>
                </div>

                <aside class="lc-preview-col">
                    <div class="lc-preview-wrap">
                        <div class="lc-preview-head">
                            <strong><?php esc_html_e('Live preview', 'logincraft'); ?></strong>
                            <span id="lc_preview_layout_badge"><?php echo esc_html($layoutLabels[(string) $options['layout']] ?? (string) $options['layout']); ?></span>
                        </div>

                        <div
                            id="logincraft-preview"
                            class="lc-preview lc-preview--<?php echo esc_attr((string) $options['layout']); ?> <?php echo $bgImageUrl !== '' ? 'is-has-bg-image' : ''; ?>"
                            data-fallback-logo="<?php echo esc_url($fallbackLogoUrl); ?>"
                            data-site-name="<?php echo esc_attr($siteName); ?>"
                            data-layout-labels="<?php echo esc_attr((string) wp_json_encode($layoutLabels)); ?>"
                            aria-hidden="true"
                        >
                            <div class="lc-preview__aside"></div>
                            <div class="lc-preview__main">
                                <div class="lc-preview__stage">
                                    <div class="lc-preview__logo <?php echo $effectiveLogoUrl === '' ? 'is-fallback' : ''; ?>">
                                        <img src="<?php echo esc_url($effectiveLogoUrl); ?>" alt="">
                                        <span class="lc-preview__site-name"><?php echo esc_html($siteName); ?></span>
                                    </div>

                                    <p class="lc-preview__welcome" <?php echo $options['welcome_text'] === '' ? 'hidden' : ''; ?>>
                                        <?php echo esc_html((string) $options['welcome_text']); ?>
                                    </p>

                                    <div class="lc-preview__card">
                                        <div class="lc-preview__field">
                                            <span><?php esc_html_e('Username or Email Address', 'logincraft'); ?></span>
                                            <div class="lc-preview__input"></div>
                                        </div>
                                        <div class="lc-preview__field">
                                            <span><?php esc_html_e('Password', 'logincraft'); ?></span>
                                            <div class="lc-preview__input"></div>
                                        </div>
                                        <div class="lc-preview__btn"><?php esc_html_e('Log In', 'logincraft'); ?></div>
                                    </div>

                                    <div class="lc-preview__links">
                                        <span><?php esc_html_e('Lost your password?', 'logincraft'); ?></span>
                                        <span class="lc-preview__back" <?php echo !empty($options['hide_back_to_blog']) ? 'hidden' : ''; ?>>
                                            &larr; <?php echo esc_html($siteName); ?>
                                        </span>
                                    </div>

                                    <p class="lc-preview__footer" <?php echo $options['footer_text'] === '' ? 'hidden' : ''; ?>>
                                        <?php echo esc_html((string) $options['footer_text']); ?>
                                    </p>
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
        <div class="lc-color-control" data-color-control data-optional="<?php echo $isOptional ? '1' : '0'; ?>">
            <label class="lc-label" for="lc_<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label>
            <div class="lc-color-row">
                <input
                    type="color"
                    value="<?php echo esc_attr($pickerValue); ?>"
                    aria-label="<?php echo esc_attr(sprintf(__('%s color picker', 'logincraft'), $label)); ?>"
                    data-color-swatch
                >
                <input
                    type="text"
                    id="lc_<?php echo esc_attr($key); ?>"
                    name="<?php echo esc_attr(LOGINCRAFT_OPTION . '[' . $key . ']'); ?>"
                    value="<?php echo esc_attr($value); ?>"
                    placeholder="<?php echo $isOptional ? esc_attr__('None', 'logincraft') : ''; ?>"
                    aria-label="<?php echo esc_attr(sprintf(__('%s hex value', 'logincraft'), $label)); ?>"
                    maxlength="7"
                    data-color-hex
                >
                <?php if ($isOptional) : ?>
                    <button
                        type="button"
                        class="button-link"
                        aria-label="<?php echo esc_attr(sprintf(__('Clear %s', 'logincraft'), $label)); ?>"
                        data-color-clear
                    ><?php esc_html_e('Clear', 'logincraft'); ?></button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
