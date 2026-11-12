<?php

declare(strict_types=1);

// Minimal WordPress stubs for a staging copy at https://taborovajizda.cz/test with index.php permalinks.
class WP_Rewrite
{
    public bool $index = true;

    public function using_index_permalinks(): bool
    {
        return $this->index;
    }
}

$GLOBALS['tj_home'] = 'https://taborovajizda.cz/test';
$GLOBALS['wp_rewrite'] = new WP_Rewrite();

function get_option(string $name): string
{
    return $name === 'home' ? $GLOBALS['tj_home'] : '';
}

function wp_parse_url(string $url, int $component = -1): mixed
{
    return parse_url($url, $component);
}

function untrailingslashit(string $value): string
{
    return rtrim($value, '/\\');
}

function esc_attr(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

require_once dirname(__DIR__) . '/app/IndexPermalinks.php';

use TaborovaJizda\Theme\IndexPermalinks;

$failures = 0;
$expect = static function (string $label, string $actual, string $expected) use (&$failures): void {
    if ($actual !== $expected) {
        fwrite(STDERR, "$label: expected $expected, got $actual\n");
        $failures++;
    }
};

$links = new IndexPermalinks();
$stage = 'https://taborovajizda.cz/test';
$cases = [
    'https://taborovajizda.cz/test/e-prihlaska/' => "$stage/index.php/e-prihlaska/",
    'http://taborovajizda.cz/test/obsah-taboroveho-kufru/' => "$stage/index.php/obsah-taboroveho-kufru/",
    'https://taborovajizda.cz/test/e-prihlaska/#menu' => "$stage/index.php/e-prihlaska/#menu",
    'https://taborovajizda.cz/test/category/aktuality/?paged=2' => "$stage/index.php/category/aktuality/?paged=2",
    'https://taborovajizda.cz/test/index.php/dokumenty/' => 'https://taborovajizda.cz/test/index.php/dokumenty/',
    'https://taborovajizda.cz/test/' => 'https://taborovajizda.cz/test/',
    'https://taborovajizda.cz/test/#dokumenty' => 'https://taborovajizda.cz/test/#dokumenty',
    'https://taborovajizda.cz/test/?page_id=20' => 'https://taborovajizda.cz/test/?page_id=20',
    'https://taborovajizda.cz/test/wp-content/uploads/2024/05/zdravotni_zpusobilost.pdf' => 'https://taborovajizda.cz/test/wp-content/uploads/2024/05/zdravotni_zpusobilost.pdf',
    'https://taborovajizda.cz/test/wp-admin/admin-ajax.php?action=x' => 'https://taborovajizda.cz/test/wp-admin/admin-ajax.php?action=x',
    'https://taborovajizda.cz/test/wp-sitemap.xml' => "$stage/index.php/wp-sitemap.xml",
    '/e-prihlaska#menu' => "$stage/index.php/e-prihlaska#menu",
    '/wp-content/uploads/2020/06/bezinfekcnost_souhlas_rodicu.pdf' => "$stage/wp-content/uploads/2020/06/bezinfekcnost_souhlas_rodicu.pdf",
    'https://taborovajizda.cz/dokumenty/' => 'https://taborovajizda.cz/dokumenty/',
    'https://www.facebook.com/taborovajizda.cz' => 'https://www.facebook.com/taborovajizda.cz',
    'mailto:info@taborovajizda.cz' => 'mailto:info@taborovajizda.cz',
    '#kontakt' => '#kontakt',
];
foreach ($cases as $input => $expected) {
    $expect("rewrite $input", $links->rewriteUrl($input), $expected);
}

$expect('home_url', $links->filterHomeUrl("$stage/odeslat-dotaz/", '/odeslat-dotaz/'), "$stage/index.php/odeslat-dotaz/");
$expect('home_url root', $links->filterHomeUrl("$stage/", '/'), "$stage/");
$expect('home_url core permalink', $links->filterHomeUrl("$stage/index.php/dokumenty/", '/index.php/dokumenty/'), "$stage/index.php/dokumenty/");

$html = '<a href="https://taborovajizda.cz/test/e-prihlaska/">A</a> <a href="https://example.com/x/">B</a> <a href="/test/wp-content/a.pdf">C</a>';
$expect(
    'html',
    $links->filterHtml($html),
    '<a href="https://taborovajizda.cz/test/index.php/e-prihlaska/">A</a> <a href="https://example.com/x/">B</a> <a href="/test/wp-content/a.pdf">C</a>'
);

$expect('request path stage', IndexPermalinks::relativeRequestPath('/test/index.php/odeslat-dotaz/'), 'odeslat-dotaz');
$expect('request path pretty', IndexPermalinks::relativeRequestPath('/test/odeslat-dotaz/'), 'odeslat-dotaz');

// Production: root install with /%postname%/ permalinks is left untouched.
$GLOBALS['tj_home'] = 'https://taborovajizda.cz';
$GLOBALS['wp_rewrite']->index = false;
$expect('production home_url', $links->filterHomeUrl('https://taborovajizda.cz/e-prihlaska/', '/e-prihlaska/'), 'https://taborovajizda.cz/e-prihlaska/');
$expect('production html', $links->filterHtml($html), $html);
$expect('production request path', IndexPermalinks::relativeRequestPath('/odeslat-dotaz/'), 'odeslat-dotaz');

if ($failures > 0) {
    exit(1);
}

fwrite(STDOUT, "Index permalink tests passed.\n");
