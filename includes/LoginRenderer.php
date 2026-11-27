<?php

declare(strict_types=1);

namespace LoginCraft;

if (!defined('ABSPATH')) {
    exit;
}

final class LoginRenderer
{
    public function register(): void
    {
        add_filter('login_headerurl', static fn (): string => home_url('/'));
        add_filter('login_headertext', static fn (): string => esc_html(get_bloginfo('name', 'display')));
        add_filter('login_body_class', [$this, 'filterBodyClass']);
        add_action('login_header', [$this, 'renderSplitAside']);
        add_filter('login_message', [$this, 'filterWelcomeMessage']);
        add_action('login_footer', [$this, 'renderFooterText']);
        add_filter('login_display_language_dropdown', [$this, 'filterLanguageDropdown']);
        add_filter('login_site_html_link', [$this, 'filterBackToBlogLink']);
        add_filter('login_errors', [$this, 'filterLoginErrors']);
        add_action('login_enqueue_scripts', [$this, 'enqueueStyles']);
    }

    /**
     * @param array<int, string> $classes
     * @return array<int, string>
     */
    public function filterBodyClass(array $classes): array
    {
        $options = Options::get();
        $layout = $this->isInterimLogin() ? 'centered' : (string) $options['layout'];

        $classes[] = 'logincraft';
        $classes[] = 'logincraft--' . $layout;

        if ($this->resolveLogoUrl($options) !== '') {
            $classes[] = 'logincraft--has-logo';
        }

        if (!$this->isInterimLogin() && $this->resolveBgImageUrl($options) !== '') {
            $classes[] = 'logincraft--has-bg-image';
        }

        return $classes;
    }

    public function renderSplitAside(): void
    {
        if ($this->isInterimLogin()) {
            return;
        }

        $options = Options::get();

        if ($options['layout'] === 'split') {
            echo '<div class="logincraft-aside" aria-hidden="true"></div>';
        }
    }

    public function filterWelcomeMessage(mixed $message): string
    {
        $message = (string) $message;
        $options = Options::get();

        if ($options['welcome_text'] === '') {
            return $message;
        }

        return '<p class="logincraft-welcome">' . esc_html((string) $options['welcome_text']) . '</p>' . $message;
    }

    public function renderFooterText(): void
    {
        if ($this->isInterimLogin()) {
            return;
        }

        $options = Options::get();

        if ($options['footer_text'] === '') {
            return;
        }

        echo '<p class="logincraft-footer">' . esc_html((string) $options['footer_text']) . '</p>';
    }

    public function filterLanguageDropdown(mixed $display): bool
    {
        $options = Options::get();

        return !empty($options['hide_language_switcher']) ? false : (bool) $display;
    }

    public function filterBackToBlogLink(mixed $htmlLink): string
    {
        $options = Options::get();

        return !empty($options['hide_back_to_blog']) ? '' : (string) $htmlLink;
    }

    public function filterLoginErrors(mixed $error): string
    {
        $error = (string) $error;
        $options = Options::get();

        if (empty($options['generic_login_errors']) || $error === '') {
            return $error;
        }

        $method = isset($_SERVER['REQUEST_METHOD'])
            ? strtoupper(sanitize_text_field(wp_unslash((string) $_SERVER['REQUEST_METHOD'])))
            : '';

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WordPress core login form submits without a nonce.
        if ($method !== 'POST' || !isset($_POST['log'])) {
            return $error;
        }

        $message = sprintf(
            '<strong>%s</strong> %s',
            esc_html__('Error:', 'logincraft'),
            esc_html__('Invalid credentials. Please check your details and try again.', 'logincraft')
        );

        return (string) apply_filters('logincraft_error_message', $message);
    }

    public function enqueueStyles(): void
    {
        $options = Options::get();
        wp_enqueue_style(
            'logincraft-styles',
            LOGINCRAFT_URL . 'assets/login.css',
            ['login'],
            LOGINCRAFT_VERSION
        );

        $bgColor = (string) $options['bg_color'];
        $bgEnd = (string) $options['bg_color_end'];
        $bgSurface = $bgEnd !== ''
            ? sprintf('linear-gradient(135deg, %s 0%%, %s 100%%)', $bgColor, $bgEnd)
            : $bgColor;

        $cardBg = (string) $options['card_bg_color'];
        $cardText = Options::contrastColor($cardBg, '#f8fafc', '#0f172a');
        $cardMuted = $cardText === '#f8fafc' ? '#cbd5e1' : '#475569';
        $cardBorder = $cardText === '#f8fafc' ? 'rgb(255 255 255 / 0.14)' : 'rgb(15 23 42 / 0.08)';

        $primary = (string) $options['primary_color'];
        $primaryText = Options::contrastColor($primary);

        $bgImageUrl = $this->isInterimLogin() ? '' : $this->resolveBgImageUrl($options);
        $hasBodyBgImage = $bgImageUrl !== '' && $options['layout'] !== 'split';
        $bgContrastSample = $bgEnd !== '' ? $bgEnd : $bgColor;
        $outerContrastSource = ($hasBodyBgImage && (int) $options['bg_overlay'] >= 35) ? '#0f172a' : $bgContrastSample;
        $outerText = $this->isInterimLogin()
            ? $cardText
            : Options::contrastColor($outerContrastSource, '#ffffff', '#0f172a');

        $logoUrl = $this->resolveLogoUrl($options);
        $overlayAlpha = number_format(((int) $options['bg_overlay']) / 100, 2, '.', '');

        $vars = [
            '--lc-bg'            => $bgColor,
            '--lc-bg-surface'    => $bgSurface,
            '--lc-card-bg'       => $cardBg,
            '--lc-card-text'     => $cardText,
            '--lc-card-muted'    => $cardMuted,
            '--lc-card-border'   => $cardBorder,
            '--lc-primary'       => $primary,
            '--lc-primary-text'  => $primaryText,
            '--lc-outer-text'    => $outerText,
            '--lc-radius'        => ((int) $options['card_radius']) . 'px',
            '--lc-logo-url'      => $this->formatCssUrl($logoUrl),
            '--lc-logo-height'   => ((int) $options['logo_height']) . 'px',
            '--lc-bg-image'      => $this->formatCssUrl($bgImageUrl),
            '--lc-overlay-alpha' => $bgImageUrl !== '' ? $overlayAlpha : '0',
        ];

        $filteredVars = apply_filters('logincraft_css_vars', $vars, $options);
        if (is_array($filteredVars)) {
            $vars = $filteredVars;
        }

        $declarations = '';
        foreach ($vars as $prop => $val) {
            if (!is_string($prop) || !preg_match('/^--[a-zA-Z0-9-]+$/', $prop)) {
                continue;
            }
            $safeVal = str_replace(['<', '>', '{', '}', ';'], '', Options::sanitizeCss((string) $val));
            if ($safeVal !== '') {
                $declarations .= $prop . ': ' . $safeVal . ';';
            }
        }

        $inlineCss = ':root{' . $declarations . '}';

        if (!empty($options['custom_css'])) {
            $inlineCss .= "\n" . Options::sanitizeCss((string) $options['custom_css']);
        }

        wp_add_inline_style('logincraft-styles', $inlineCss);
    }

    private function isInterimLogin(): bool
    {
        global $interim_login;

        if (!empty($interim_login)) {
            return true;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Core login modal query parameter.
        return isset($_REQUEST['interim-login']);
    }

    private function formatCssUrl(string $url): string
    {
        if ($url === '') {
            return 'none';
        }

        $scheme = wp_parse_url($url, PHP_URL_SCHEME);
        if (!in_array(strtolower((string) $scheme), ['http', 'https'], true)) {
            return 'none';
        }

        $safe = str_replace(['"', "'", '(', ')', '\\', '<', '>', "\r", "\n"], '', esc_url_raw($url));

        return $safe !== '' ? 'url("' . $safe . '")' : 'none';
    }

    /**
     * @param array<string, mixed> $options
     */
    private function resolveLogoUrl(array $options): string
    {
        $logoId = absint($options['logo_id'] ?? 0);

        if ($logoId === 0) {
            $logoId = absint(get_theme_mod('custom_logo'));
        }

        if ($logoId === 0) {
            return '';
        }

        return wp_get_attachment_image_url($logoId, 'medium') ?: '';
    }

    /**
     * @param array<string, mixed> $options
     */
    private function resolveBgImageUrl(array $options): string
    {
        $bgImageId = absint($options['bg_image_id'] ?? 0);

        if ($bgImageId === 0) {
            return '';
        }

        return wp_get_attachment_image_url($bgImageId, 'full') ?: '';
    }
}
