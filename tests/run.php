<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use EstroLogin\AdminPage;
use EstroLogin\LoginRenderer;
use EstroLogin\Options;

$assertions = 0;

function el_assert(bool $condition, string $message): void
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
el_assert($defaults['layout'] === 'centered', 'default layout is centered');
el_assert($defaults['hide_language_switcher'] === true, 'default hides language switcher');
el_assert($defaults['generic_login_errors'] === true, 'default enables generic login errors');

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

el_assert($sanitized['layout'] === 'centered', 'non-scalar layout falls back to centered');
el_assert($sanitized['logo_id'] === 15, 'logo_id normalized via absint');
el_assert($sanitized['logo_height'] === 160, 'logo_height clamped to max 160');
el_assert($sanitized['bg_color'] === '#f1f5f9', 'invalid bg_color falls back to default');
el_assert($sanitized['bg_color_end'] === '#aabbcc', '3-char bg_color_end expands to 6-char hex');
el_assert($sanitized['primary_color'] === '#ffffff', '3-char primary_color expands to 6-char hex');
el_assert($sanitized['bg_overlay'] === 90, 'bg_overlay clamped to max 90');
el_assert($sanitized['card_radius'] === 8, 'invalid radius falls back to 8');
el_assert(!str_contains($sanitized['welcome_text'], '<script>'), 'welcome_text strips HTML');
el_assert(mb_strlen($sanitized['footer_text']) === 160, 'footer_text capped at 160 chars');
el_assert($sanitized['hide_language_switcher'] === false, 'boolean false preserved');
el_assert($sanitized['hide_back_to_blog'] === true, 'boolean true preserved');
el_assert(!str_contains($sanitized['custom_css'], '<script>'), 'custom_css strips script tags');
el_assert(stripos($sanitized['custom_css'], '</style') === false, 'custom_css strips closing style tags');
el_assert(stripos($sanitized['custom_css'], '@import') === false, 'custom_css strips @import');

// 2. Iterative CSS sanitization against nested/comment bypasses
$cssBypass = '/* comment */ <style> javajavascript:script:alert(1) expreexpression(ssion(1) behavbehavior:ior:url(x) -moz-bind-moz-binding:ing:url(x) data:text/html .login { border-radius: 8px; }';
$cleanCss = Options::sanitizeCss($cssBypass);
el_assert(stripos($cleanCss, 'javascript:') === false, 'iterative sanitizeCss blocks nested javascript:');
el_assert(stripos($cleanCss, 'expression(') === false, 'iterative sanitizeCss blocks nested expression(');
el_assert(stripos($cleanCss, 'behavior:') === false, 'iterative sanitizeCss blocks nested behavior:');
el_assert(stripos($cleanCss, '-moz-binding:') === false, 'iterative sanitizeCss blocks nested -moz-binding:');
el_assert(stripos($cleanCss, 'data:') === false, 'sanitizeCss blocks data:');
el_assert(!str_contains($cleanCss, '<') && !str_contains($cleanCss, '>'), 'sanitizeCss strips angle brackets');
el_assert(str_contains($cleanCss, '.login { border-radius: 8px; }'), 'sanitizeCss preserves valid CSS');

// 3. WCAG 2.1 contrast calculation
el_assert(Options::contrastColor('#0f172a') === '#ffffff', 'dark bg gets white foreground');
el_assert(Options::contrastColor('#ffffff') === '#0f172a', 'white bg gets dark foreground');
el_assert(Options::contrastColor('#16a34a') === '#0f172a', 'mid-luminance green picks dark foreground for >=4.5:1');
el_assert(Options::contrastColor('#fff') === '#0f172a', '3-char hex expands properly');
el_assert(Options::contrastColor('invalid') === '#0f172a', 'invalid hex falls back safely');

// 4. Options filters (`estro_login_defaults`, `estro_login_options`)
$GLOBALS['el_test_filters']['estro_login_defaults'][] = static function (array $d): array {
    $d['primary_color'] = '#2563eb';
    return $d;
};
el_assert(Options::defaults()['primary_color'] === '#2563eb', 'estro_login_defaults filter applies');
$GLOBALS['el_test_filters'] = [];

$GLOBALS['el_test_options'][ESTRO_LOGIN_OPTION] = ['bg_color' => '#ffffff'];
$GLOBALS['el_test_filters']['estro_login_options'][] = static function (array $o): array {
    $o['primary_color'] = '<script>';
    return $o;
};
$filteredOpts = Options::get();
el_assert($filteredOpts['primary_color'] === '#0f172a', 'Options::get re-sanitizes filtered output');
$GLOBALS['el_test_filters'] = [];

// 5. LoginRenderer body classes, interim-login, welcome, footer, language, back link
$GLOBALS['el_test_options'][ESTRO_LOGIN_OPTION] = [
    'layout'                 => 'split',
    'logo_id'                => 10,
    'bg_image_id'            => 20,
    'welcome_text'           => 'Welcome & "Team" <b>Hi</b>',
    'footer_text'            => 'Support & Co',
    'hide_language_switcher' => true,
    'hide_back_to_blog'      => true,
];
$GLOBALS['el_test_attachments'] = [
    10 => 'https://example.test/logo.png',
    20 => 'https://example.test/bg.jpg',
];

$renderer = new LoginRenderer();
unset($_REQUEST['interim-login']);
$classes = $renderer->filterBodyClass(['login']);
el_assert(in_array('estro-login--split', $classes, true), 'split class added');
el_assert(in_array('estro-login--has-logo', $classes, true), 'has-logo class added');
el_assert(in_array('estro-login--has-bg-image', $classes, true), 'has-bg-image class added');
el_assert(($GLOBALS['el_test_attachment_sizes'][10] ?? '') === 'full', 'logo attachment requests full size for Retina clarity');

$welcomeHtml = $renderer->filterWelcomeMessage('<div>Notice</div>');
el_assert(str_contains($welcomeHtml, '<p class="estro-login-welcome">Welcome &amp; &quot;Team&quot; Hi</p><div>Notice</div>'), 'welcome_text strips tags, escapes HTML, and prepends');

ob_start();
$renderer->renderFooterText();
$footerHtml = ob_get_clean();
el_assert($footerHtml === '<p class="estro-login-footer">Support &amp; Co</p>', 'footer_text escaped and rendered');

el_assert($renderer->filterLanguageDropdown(true) === false, 'language dropdown hidden when enabled');
el_assert($renderer->filterBackToBlogLink('<a href="/">Back</a>') === '', 'back link hidden when enabled');

$_REQUEST['interim-login'] = '1';
$interimClasses = $renderer->filterBodyClass(['login']);
el_assert(in_array('estro-login--centered', $interimClasses, true), 'interim-login forces centered layout');
el_assert(!in_array('estro-login--has-bg-image', $interimClasses, true), 'interim-login skips bg image');
ob_start();
$renderer->renderSplitAside();
el_assert(ob_get_clean() === '', 'interim-login skips split aside');
ob_start();
$renderer->renderFooterText();
el_assert(ob_get_clean() === '', 'interim-login skips footer text');
unset($_REQUEST['interim-login']);

// 6. Generic login error applies only on failed login POST
$GLOBALS['el_test_options'][ESTRO_LOGIN_OPTION] = ['generic_login_errors' => true];
$_SERVER['REQUEST_METHOD'] = 'GET';
unset($_POST['log']);
el_assert($renderer->filterLoginErrors('Session expired') === 'Session expired', 'GET errors stay untouched');

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['log'] = 'admin';
$masked = $renderer->filterLoginErrors('Unknown username');
el_assert(str_contains($masked, 'Invalid credentials'), 'failed login POST masks username enumeration');
unset($_POST['log']);

// 7. Enqueue styles generates sanitized CSS custom vars + blocks evil filter injection
$GLOBALS['el_test_options'][ESTRO_LOGIN_OPTION] = [
    'card_bg_color' => '#111827',
    'primary_color' => '#38bdf8',
    'card_radius'   => 16,
];
$GLOBALS['el_test_filters']['estro_login_css_vars'][] = static function (array $vars): array {
    $vars['evil-key'] = 'bad';
    $vars['--el-radius'] = '12px;}</style><script>alert(1)</script>';
    return $vars;
};
$renderer->enqueueStyles();
$inlineCss = $GLOBALS['el_test_inline_styles']['estro-login-styles'] ?? '';
el_assert(str_contains($inlineCss, '--el-card-text: #f8fafc'), 'dark card picks light text');
el_assert(!str_contains($inlineCss, 'evil-key'), 'invalid CSS var keys dropped');
el_assert(stripos($inlineCss, '</style') === false, 'CSS var values cannot break out of style tag');
$GLOBALS['el_test_filters'] = [];

// Split layout with light bg_color + bg_image still computes dark outer text for mobile (< 900px)
$GLOBALS['el_test_options'][ESTRO_LOGIN_OPTION] = [
    'layout'      => 'split',
    'bg_color'    => '#f1f5f9',
    'bg_image_id' => 20,
    'bg_overlay'  => 60,
];
$renderer->enqueueStyles();
$splitInlineCss = $GLOBALS['el_test_inline_styles']['estro-login-styles'] ?? '';
el_assert(str_contains($splitInlineCss, '--el-outer-text: #0f172a'), 'split mobile outer text uses bg_color contrast');

// 8. AdminPage capability guard + render + presets validation
$admin = new AdminPage();
$GLOBALS['el_test_can_manage'] = false;
ob_start();
$admin->renderPage();
el_assert(ob_get_clean() === '', 'unauthorized user gets empty renderPage output');

$GLOBALS['el_test_can_manage'] = true;
ob_start();
$admin->renderPage();
$adminHtml = ob_get_clean();
el_assert(str_contains($adminHtml, 'id="estro-login-preview"'), 'authorized render includes live preview');
el_assert(str_contains($adminHtml, 'data-transfer-export') && str_contains($adminHtml, 'data-transfer-import'), 'authorized render includes Export & Import controls');

$composer = json_decode((string) file_get_contents(dirname(__DIR__) . '/composer.json'), true);
el_assert(is_array($composer) && ($composer['name'] ?? '') === 'heyrenan/estro-login' && ($composer['type'] ?? '') === 'wordpress-plugin', 'composer.json defines heyrenan/estro-login wordpress-plugin package');

$presets = AdminPage::presets();
el_assert(count($presets) === 4, '4 curated presets defined');
foreach ($presets as $slug => $preset) {
    el_assert(!empty($preset['label']), "preset {$slug} has label");
    el_assert(in_array($preset['layout'], Options::LAYOUTS, true), "preset {$slug} valid layout");
    el_assert(in_array($preset['card_radius'], Options::RADII, true), "preset {$slug} valid radius");
    el_assert(sanitize_hex_color($preset['bg_color']) !== null, "preset {$slug} valid bg_color");
    el_assert(sanitize_hex_color($preset['card_bg_color']) !== null, "preset {$slug} valid card_bg_color");
    el_assert(sanitize_hex_color($preset['primary_color']) !== null, "preset {$slug} valid primary_color");
}

// 9. Translation parity: every string in AdminPage & LoginRenderer exists in top-level pt_BR messages and POT
$l10n = require dirname(__DIR__) . '/languages/estro-login-pt_BR.l10n.php';
$messages = $l10n['messages'] ?? [];
$potContent = (string) file_get_contents(dirname(__DIR__) . '/languages/estro-login.pot');
el_assert(is_array($messages) && count($messages) > 0, 'l10n file uses WP 6.5+ top-level messages array');
$phpFiles = [
    dirname(__DIR__) . '/includes/AdminPage.php',
    dirname(__DIR__) . '/includes/LoginRenderer.php',
];
foreach ($phpFiles as $file) {
    $src = (string) file_get_contents($file);
    preg_match_all("/(?:__|esc_html__|esc_attr__|esc_html_e|esc_attr_e)\(\s*'([^']+)'\s*,\s*'estro-login'\s*\)/", $src, $matches);
    foreach ($matches[1] as $msgid) {
        $normalized = str_replace("\\'", "'", $msgid);
        $potEscaped = str_replace('"', '\"', $normalized);
        el_assert(
            isset($messages[$normalized]) && is_string($messages[$normalized]) && $messages[$normalized] !== '',
            "pt_BR translation missing or non-string key: {$normalized}"
        );
        el_assert(
            str_contains($potContent, "msgid \"{$potEscaped}\""),
            "POT file missing msgid: {$normalized}"
        );
    }
}

echo "OK: {$assertions} assertions passed.\n";
