<?php

declare(strict_types=1);

namespace EstroLogin;

if (!defined('ABSPATH')) {
    exit;
}

final class Options
{
    public const LAYOUTS = ['centered', 'split', 'minimal'];
    public const RADII = [0, 8, 16];

    /**
     * Return default plugin options.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        $defaults = [
            'layout'                 => 'centered',
            'logo_id'                => 0,
            'logo_height'            => 64,
            'bg_color'               => '#f1f5f9',
            'bg_color_end'           => '',
            'bg_image_id'            => 0,
            'bg_overlay'             => 45,
            'card_bg_color'          => '#ffffff',
            'primary_color'          => '#0f172a',
            'card_radius'            => 8,
            'welcome_text'           => '',
            'footer_text'            => '',
            'hide_language_switcher' => true,
            'hide_back_to_blog'      => false,
            'generic_login_errors'   => true,
            'custom_css'             => '',
        ];

        return (array) apply_filters('estro_login_defaults', $defaults);
    }

    /**
     * Retrieve sanitized plugin options merged with defaults.
     *
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        $saved = get_option(ESTRO_LOGIN_OPTION, null);
        $options = (is_array($saved) && $saved !== []) ? self::sanitize($saved) : self::defaults();
        $filtered = apply_filters('estro_login_options', $options);

        return is_array($filtered) ? self::sanitize($filtered) : $options;
    }

    /**
     * Sanitize and normalize plugin options.
     *
     * @return array<string, mixed>
     */
    public static function sanitize(mixed $input): array
    {
        $defaults = self::defaults();

        if (!is_array($input)) {
            return $defaults;
        }

        $layout = self::scalarString($input['layout'] ?? null, (string) $defaults['layout']);
        if (!in_array($layout, self::LAYOUTS, true)) {
            $layout = (string) $defaults['layout'];
        }

        $logoHeight = absint(is_scalar($input['logo_height'] ?? null) ? $input['logo_height'] : $defaults['logo_height']);
        $logoHeight = max(24, min(160, $logoHeight));

        $bgOverlay = isset($input['bg_overlay']) && is_scalar($input['bg_overlay'])
            ? absint($input['bg_overlay'])
            : (int) $defaults['bg_overlay'];
        $bgOverlay = max(0, min(90, $bgOverlay));

        $cardRadius = isset($input['card_radius']) && is_scalar($input['card_radius'])
            ? absint($input['card_radius'])
            : (int) $defaults['card_radius'];
        if (!in_array($cardRadius, self::RADII, true)) {
            $cardRadius = (int) $defaults['card_radius'];
        }

        $bgColorEndRaw = trim(self::scalarString($input['bg_color_end'] ?? null));
        $bgColorEnd = $bgColorEndRaw === '' ? '' : self::normalizeHex($bgColorEndRaw, '');

        return [
            'layout'                 => $layout,
            'logo_id'                => is_scalar($input['logo_id'] ?? null) ? absint($input['logo_id']) : 0,
            'logo_height'            => $logoHeight,
            'bg_color'               => self::normalizeHex(self::scalarString($input['bg_color'] ?? null), (string) $defaults['bg_color']),
            'bg_color_end'           => $bgColorEnd,
            'bg_image_id'            => is_scalar($input['bg_image_id'] ?? null) ? absint($input['bg_image_id']) : 0,
            'bg_overlay'             => $bgOverlay,
            'card_bg_color'          => self::normalizeHex(self::scalarString($input['card_bg_color'] ?? null), (string) $defaults['card_bg_color']),
            'primary_color'          => self::normalizeHex(self::scalarString($input['primary_color'] ?? null), (string) $defaults['primary_color']),
            'card_radius'            => $cardRadius,
            'welcome_text'           => mb_substr(sanitize_text_field(self::scalarString($input['welcome_text'] ?? null)), 0, 160),
            'footer_text'            => mb_substr(sanitize_text_field(self::scalarString($input['footer_text'] ?? null)), 0, 160),
            'hide_language_switcher' => !empty($input['hide_language_switcher']),
            'hide_back_to_blog'      => !empty($input['hide_back_to_blog']),
            'generic_login_errors'   => !empty($input['generic_login_errors']),
            'custom_css'             => self::sanitizeCss(self::scalarString($input['custom_css'] ?? null)),
        ];
    }

    /**
     * Strip HTML tags, comments, and unsafe CSS directives iteratively.
     */
    public static function sanitizeCss(string $css): string
    {
        $clean = wp_strip_all_tags($css);
        $clean = (string) preg_replace('/\/\*[\s\S]*?\*\//', '', $clean);
        $clean = str_replace(['<', '>'], '', $clean);

        $pattern = '/<\/?\s*style|javascript\s*:|vbscript\s*:|data\s*:|expression\s*\(|@import\b|-moz-binding\s*:|behavior\s*:/i';

        do {
            $previous = $clean;
            $clean = (string) preg_replace($pattern, '', $clean);
        } while ($clean !== $previous);

        return trim($clean);
    }

    /**
     * Pick light or dark foreground hex color using WCAG 2.1 relative luminance.
     */
    public static function contrastColor(string $hexColor, string $light = '#ffffff', string $dark = '#0f172a'): string
    {
        $expanded = self::normalizeHex($hexColor, '');

        if ($expanded === '') {
            return $dark;
        }

        $clean = ltrim($expanded, '#');
        $channels = [
            hexdec(substr($clean, 0, 2)) / 255,
            hexdec(substr($clean, 2, 2)) / 255,
            hexdec(substr($clean, 4, 2)) / 255,
        ];

        $linear = array_map(
            static fn (float $c): float => $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4,
            $channels
        );

        $luminance = 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];

        return $luminance < 0.198 ? $light : $dark;
    }

    private static function scalarString(mixed $value, string $fallback = ''): string
    {
        return is_scalar($value) ? (string) $value : $fallback;
    }

    private static function normalizeHex(string $value, string $fallback): string
    {
        $sanitized = sanitize_hex_color(trim($value));

        if (!is_string($sanitized) || $sanitized === '') {
            return $fallback;
        }

        $clean = ltrim($sanitized, '#');
        if (strlen($clean) === 3) {
            return strtolower('#' . $clean[0] . $clean[0] . $clean[1] . $clean[1] . $clean[2] . $clean[2]);
        }

        return strtolower($sanitized);
    }
}
