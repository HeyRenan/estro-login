<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use LoginCraft\AdminPage;
use LoginCraft\LoginRenderer;
use LoginCraft\Options;

$assertions = 0;

function lc_assert(bool $condition, string $message): void
{
    global $assertions;
    $assertions++;
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

// 1. Defaults & sanitization clamps + non-scalar guards + hex expansion
$defaults = Options::defaults();
lc_assert($defaults['layout'] === 'centered', 'default layout is centered');
lc_assert($defaults['hide_language_switcher'] === true, 'default hides language switcher');
lc_assert($defaults['generic_login_errors'] === true, 'default enables generic login errors');

$sanitized = Options::sanitize([
    'layout'                 => ['array-attack'],
    'logo_id'                => '-15',
    'logo_height'            => 500,
    'bg_color'               => 'not-hex',
    'bg_color_end'           => '#abc',
    'bg_image_id'            => '44',
    'bg_overlay'             => 150,
    'card_bg_color'          => '#0f172a',
    'primary_color'          => '#fff',
    'card_radius'            => 99,
    'welcome_text'           => '<script>alert(1)</script>Hello <b>world</b>',
    'footer_text'            => str_repeat('a', 200),
    'hide_language_switcher' => 0,
    'hide_back_to_blog'      => 1,
    'generic_login_errors'   => 0,
    'custom_css'             => '</style><script>alert(1)</script>@import url(x); body { color: #000; }',
]);

lc_assert($sanitized['layout'] === 'centered', 'non-scalar layout falls back to centered');
lc_assert($sanitized['logo_id'] === 15, 'logo_id normalized via absint');
lc_assert($sanitized['logo_height'] === 160, 'logo_height clamped to max 160');
lc_assert($sanitized['bg_color'] === '#f1f5f9', 'invalid bg_color falls back to default');
lc_assert($sanitized['bg_color_end'] === '#aabbcc', '3-char bg_color_end expands to 6-char hex');
lc_assert($sanitized['primary_color'] === '#ffffff', '3-char primary_color expands to 6-char hex');
lc_assert($sanitized['bg_overlay'] === 90, 'bg_overlay clamped to max 90');
lc_assert($sanitized['card_radius'] === 8, 'invalid radius falls back to 8');
lc_assert(!str_contains($sanitized['welcome_text'], '<script>'), 'welcome_text strips HTML');
lc_assert(mb_strlen($sanitized['footer_text']) === 160, 'footer_text capped at 160 chars');
lc_assert($sanitized['hide_language_switcher'] === false, 'boolean false preserved');
lc_assert($sanitized['hide_back_to_blog'] === true, 'boolean true preserved');
lc_assert(!str_contains($sanitized['custom_css'], '<script>'), 'custom_css strips script tags');
lc_assert(stripos($sanitized['custom_css'], '</style') === false, 'custom_css strips closing style tags');
lc_assert(stripos($sanitized['custom_css'], '@import') === false, 'custom_css strips @import');

// 2. Iterative CSS sanitization against nested/comment bypasses
$cssBypass = '/* comment */ <style> javajavascript:script:alert(1) expreexpression(ssion(1) behavbehavior:ior:url(x) -moz-bind-moz-binding:ing:url(x) data:text/html .login { border-radius: 8px; }';
$cleanCss = Options::sanitizeCss($cssBypass);
lc_assert(stripos($cleanCss, 'javascript:') === false, 'iterative sanitizeCss blocks nested javascript:');
lc_assert(stripos($cleanCss, 'expression(') === false, 'iterative sanitizeCss blocks nested expression(');
lc_assert(stripos($cleanCss, 'behavior:') === false, 'iterative sanitizeCss blocks nested behavior:');
lc_assert(stripos($cleanCss, '-moz-binding:') === false, 'iterative sanitizeCss blocks nested -moz-binding:');
lc_assert(stripos($cleanCss, 'data:') === false, 'sanitizeCss blocks data:');
lc_assert(!str_contains($cleanCss, '<') && !str_contains($cleanCss, '>'), 'sanitizeCss strips angle brackets');
lc_assert(str_contains($cleanCss, '.login { border-radius: 8px; }'), 'sanitizeCss preserves valid CSS');

// 3. WCAG 2.1 contrast calculation
lc_assert(Options::contrastColor('#0f172a') === '#ffffff', 'dark bg gets white foreground');
lc_assert(Options::contrastColor('#ffffff') === '#0f172a', 'white bg gets dark foreground');
lc_assert(Options::contrastColor('#16a34a') === '#0f172a', 'mid-luminance green picks dark foreground for >=4.5:1');
lc_assert(Options::contrastColor('#fff') === '#0f172a', '3-char hex expands properly');
lc_assert(Options::contrastColor('invalid') === '#0f172a', 'invalid hex falls back safely');

// 4. Options filters (`logincraft_defaults`, `logincraft_options`)
$GLOBALS['lc_test_filters']['logincraft_defaults'][] = static function (array $d): array {
    $d['primary_color'] = '#2563eb';
    return $d;
};
lc_assert(Options::defaults()['primary_color'] === '#2563eb', 'logincraft_defaults filter applies');
$GLOBALS['lc_test_filters'] = [];

$GLOBALS['lc_test_options'][LOGINCRAFT_OPTION] = ['bg_color' => '#ffffff'];
$GLOBALS['lc_test_filters']['logincraft_options'][] = static function (array $o): array {
    $o['primary_color'] = '<script>';
    return $o;
};
$filteredOpts = Options::get();
lc_assert($filteredOpts['primary_color'] === '#0f172a', 'Options::get re-sanitizes filtered output');
$GLOBALS['lc_test_filters'] = [];

// 5. LoginRenderer body classes, interim-login, welcome, footer, language, back link
$GLOBALS['lc_test_options'][LOGINCRAFT_OPTION] = [
    'layout'                 => 'split',
    'logo_id'                => 10,
    'bg_image_id'            => 20,
    'welcome_text'           => 'Welcome & "Team" <b>Hi</b>',
    'footer_text'            => 'Support & Co',
    'hide_language_switcher' => true,
    'hide_back_to_blog'      => true,
];
$GLOBALS['lc_test_attachments'] = [
    10 => 'https://example.test/logo.png',
    20 => 'https://example.test/bg.jpg',
];

$renderer = new LoginRenderer();
unset($_REQUEST['interim-login']);
$classes = $renderer->filterBodyClass(['login']);
lc_assert(in_array('logincraft--split', $classes, true), 'split class added');
lc_assert(in_array('logincraft--has-logo', $classes, true), 'has-logo class added');
lc_assert(in_array('logincraft--has-bg-image', $classes, true), 'has-bg-image class added');

$welcomeHtml = $renderer->filterWelcomeMessage('<div>Notice</div>');
lc_assert(str_contains($welcomeHtml, '<p class="logincraft-welcome">Welcome &amp; &quot;Team&quot; Hi</p><div>Notice</div>'), 'welcome_text strips tags, escapes HTML, and prepends');

ob_start();
$renderer->renderFooterText();
$footerHtml = ob_get_clean();
lc_assert($footerHtml === '<p class="logincraft-footer">Support &amp; Co</p>', 'footer_text escaped and rendered');

lc_assert($renderer->filterLanguageDropdown(true) === false, 'language dropdown hidden when enabled');
lc_assert($renderer->filterBackToBlogLink('<a href="/">Back</a>') === '', 'back link hidden when enabled');

$_REQUEST['interim-login'] = '1';
$interimClasses = $renderer->filterBodyClass(['login']);
lc_assert(in_array('logincraft--centered', $interimClasses, true), 'interim-login forces centered layout');
lc_assert(!in_array('logincraft--has-bg-image', $interimClasses, true), 'interim-login skips bg image');
ob_start();
$renderer->renderSplitAside();
lc_assert(ob_get_clean() === '', 'interim-login skips split aside');
ob_start();
$renderer->renderFooterText();
lc_assert(ob_get_clean() === '', 'interim-login skips footer text');
unset($_REQUEST['interim-login']);

// 6. Generic login error applies only on failed login POST
$GLOBALS['lc_test_options'][LOGINCRAFT_OPTION] = ['generic_login_errors' => true];
$_SERVER['REQUEST_METHOD'] = 'GET';
unset($_POST['log']);
lc_assert($renderer->filterLoginErrors('Session expired') === 'Session expired', 'GET errors stay untouched');

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['log'] = 'admin';
$masked = $renderer->filterLoginErrors('Unknown username');
lc_assert(str_contains($masked, 'Invalid credentials'), 'failed login POST masks username enumeration');
unset($_POST['log']);

// 7. Enqueue styles generates sanitized CSS custom vars + blocks evil filter injection
$GLOBALS['lc_test_options'][LOGINCRAFT_OPTION] = [
    'card_bg_color' => '#111827',
    'primary_color' => '#38bdf8',
    'card_radius'   => 16,
];
$GLOBALS['lc_test_filters']['logincraft_css_vars'][] = static function (array $vars): array {
    $vars['evil-key'] = 'bad';
    $vars['--lc-radius'] = '12px;}</style><script>alert(1)</script>';
    return $vars;
};
$renderer->enqueueStyles();
$inlineCss = $GLOBALS['lc_test_inline_styles']['logincraft-styles'] ?? '';
lc_assert(str_contains($inlineCss, '--lc-card-text: #f8fafc'), 'dark card picks light text');
lc_assert(!str_contains($inlineCss, 'evil-key'), 'invalid CSS var keys dropped');
lc_assert(stripos($inlineCss, '</style') === false, 'CSS var values cannot break out of style tag');
$GLOBALS['lc_test_filters'] = [];

// Split layout with light bg_color + bg_image still computes dark outer text for mobile (< 900px)
$GLOBALS['lc_test_options'][LOGINCRAFT_OPTION] = [
    'layout'      => 'split',
    'bg_color'    => '#f1f5f9',
    'bg_image_id' => 20,
    'bg_overlay'  => 60,
];
$renderer->enqueueStyles();
$splitInlineCss = $GLOBALS['lc_test_inline_styles']['logincraft-styles'] ?? '';
lc_assert(str_contains($splitInlineCss, '--lc-outer-text: #0f172a'), 'split mobile outer text uses bg_color contrast');

// 8. AdminPage capability guard + render + presets validation
$admin = new AdminPage();
$GLOBALS['lc_test_can_manage'] = false;
ob_start();
$admin->renderPage();
lc_assert(ob_get_clean() === '', 'unauthorized user gets empty renderPage output');

$GLOBALS['lc_test_can_manage'] = true;
ob_start();
$admin->renderPage();
$adminHtml = ob_get_clean();
lc_assert(str_contains($adminHtml, 'id="logincraft-preview"'), 'authorized render includes live preview');

$presets = AdminPage::presets();
lc_assert(count($presets) === 4, '4 curated presets defined');
foreach ($presets as $slug => $preset) {
    lc_assert(!empty($preset['label']), "preset {$slug} has label");
    lc_assert(in_array($preset['layout'], Options::LAYOUTS, true), "preset {$slug} valid layout");
    lc_assert(in_array($preset['card_radius'], Options::RADII, true), "preset {$slug} valid radius");
    lc_assert(sanitize_hex_color($preset['bg_color']) !== null, "preset {$slug} valid bg_color");
    lc_assert(sanitize_hex_color($preset['card_bg_color']) !== null, "preset {$slug} valid card_bg_color");
    lc_assert(sanitize_hex_color($preset['primary_color']) !== null, "preset {$slug} valid primary_color");
}

// 9. Translation parity: every string in AdminPage & LoginRenderer exists in top-level pt_BR messages and POT
$l10n = require dirname(__DIR__) . '/languages/logincraft-pt_BR.l10n.php';
$messages = $l10n['messages'] ?? [];
$potContent = (string) file_get_contents(dirname(__DIR__) . '/languages/logincraft.pot');
lc_assert(is_array($messages) && count($messages) > 0, 'l10n file uses WP 6.5+ top-level messages array');
$phpFiles = [
    dirname(__DIR__) . '/includes/AdminPage.php',
    dirname(__DIR__) . '/includes/LoginRenderer.php',
];
foreach ($phpFiles as $file) {
    $src = (string) file_get_contents($file);
    preg_match_all("/(?:__|esc_html__|esc_attr__|esc_html_e|esc_attr_e)\(\s*'([^']+)'\s*,\s*'logincraft'\s*\)/", $src, $matches);
    foreach ($matches[1] as $msgid) {
        $normalized = str_replace("\\'", "'", $msgid);
        $potEscaped = str_replace('"', '\"', $normalized);
        lc_assert(
            isset($messages[$normalized]) && is_string($messages[$normalized]) && $messages[$normalized] !== '',
            "pt_BR translation missing or non-string key: {$normalized}"
        );
        lc_assert(
            str_contains($potContent, "msgid \"{$potEscaped}\""),
            "POT file missing msgid: {$normalized}"
        );
    }
}

echo "OK: {$assertions} assertions passed.\n";
