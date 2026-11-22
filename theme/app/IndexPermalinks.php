<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme;

/**
 * Support for "PATHINFO" permalinks (`/index.php/%postname%/`).
 *
 * The WP Staging copy lives in the `/test/` subdirectory of a server whose
 * nginx routes every non-existing path to the production WordPress. Only
 * `/test/index.php/...` reaches the staging PHP, so staging uses index.php
 * permalinks. WordPress adds `index.php` to the links it builds itself, but
 * not to `home_url('/slug/')` calls in templates nor to links written into
 * post content, menus and widgets. This class adds it to those same-site
 * links, so nothing on staging leads to production.
 *
 * Everything is a no-op unless the site really uses index.php permalinks,
 * so production (`/%postname%/`) is unaffected.
 */
final class IndexPermalinks
{
    /** First path segments that are real files or directories and must stay untouched. */
    private const PASSTHROUGH = ['index.php', 'wp-admin', 'wp-content', 'wp-includes', 'wp-login.php', 'wp-cron.php', 'wp-signup.php', 'xmlrpc.php'];

    public function register(): void
    {
        add_filter('home_url', [$this, 'filterHomeUrl'], 10, 2);
        foreach (['the_content', 'widget_text_content', 'widget_block_content', 'the_excerpt'] as $hook) {
            add_filter($hook, [$this, 'filterHtml'], 99);
        }
        add_filter('nav_menu_link_attributes', [$this, 'filterMenuLink'], 20);
    }

    public static function active(): bool
    {
        global $wp_rewrite;

        return $wp_rewrite instanceof \WP_Rewrite && $wp_rewrite->using_index_permalinks();
    }

    /**
     * Path of a request relative to the site home, without a leading `index.php/`.
     * `/test/index.php/odeslat-dotaz/` on a site at `/test/` gives `odeslat-dotaz`.
     */
    public static function relativeRequestPath(string $path): string
    {
        $path = trim($path, '/');
        $home = trim((string) wp_parse_url((string) get_option('home'), PHP_URL_PATH), '/');
        if ($home !== '' && ($path === $home || str_starts_with($path, $home . '/'))) {
            $path = ltrim(substr($path, strlen($home)), '/');
        }
        if ($path === 'index.php' || str_starts_with($path, 'index.php/')) {
            $path = ltrim(substr($path, strlen('index.php')), '/');
        }

        return $path;
    }

    public function filterHomeUrl(string $url, string $path): string
    {
        return self::active() && $this->needsIndex(ltrim($path, '/')) ? $this->rewriteUrl($url) : $url;
    }

    public function filterHtml(string $html): string
    {
        if (! self::active() || $html === '' || ! str_contains($html, 'href')) {
            return $html;
        }

        return (string) preg_replace_callback(
            '/\bhref=(["\'])([^"\']+)\1/i',
            function (array $m): string {
                $url = html_entity_decode($m[2], ENT_QUOTES);
                $rewritten = $this->rewriteUrl($url);

                return $rewritten === $url ? $m[0] : 'href=' . $m[1] . esc_attr($rewritten) . $m[1];
            },
            $html
        );
    }

    /** @param array<string, mixed> $attributes */
    public function filterMenuLink(array $attributes): array
    {
        if (self::active() && isset($attributes['href']) && is_string($attributes['href'])) {
            $attributes['href'] = $this->rewriteUrl($attributes['href']);
        }

        return $attributes;
    }

    /** Add `index.php/` to an absolute or root-relative link of this site. Other links are returned unchanged. */
    public function rewriteUrl(string $url): string
    {
        $home = untrailingslashit((string) get_option('home'));
        $homeHost = (string) wp_parse_url($home, PHP_URL_HOST);
        $homePath = trim((string) wp_parse_url($home, PHP_URL_PATH), '/');
        $parts = wp_parse_url($url);
        if (! is_array($parts) || isset($parts['user'])) {
            return $url;
        }
        $host = $parts['host'] ?? null;
        if ($host === null && ! str_starts_with($url, '/')) {
            return $url;
        }
        if ($host !== null && strcasecmp(preg_replace('/^www\./i', '', $host), preg_replace('/^www\./i', '', $homeHost)) !== 0) {
            return $url;
        }
        $path = trim((string) ($parts['path'] ?? ''), '/');
        $outsideHome = false;
        if ($homePath !== '') {
            if ($path === $homePath || str_starts_with($path, $homePath . '/')) {
                $path = ltrim(substr($path, strlen($homePath)), '/');
            } elseif ($host !== null) {
                // An absolute link outside the subdirectory belongs to another site on the host.
                return $url;
            } else {
                // A root-relative link (`/e-prihlaska/`) was written for a root install; keep it on this site.
                $outsideHome = true;
            }
        }
        $needsIndex = $this->needsIndex($path);
        if (! $needsIndex && ! $outsideHome) {
            return $url;
        }
        $trailing = $path !== '' && str_ends_with((string) ($parts['path'] ?? ''), '/') ? '/' : '';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return $home . '/' . ($needsIndex ? 'index.php/' : '') . $path . $trailing . $query . $fragment;
    }

    private function needsIndex(string $path): bool
    {
        $path = trim(strtok($path, '?#') ?: '', '/');
        if ($path === '') {
            return false;
        }
        $first = explode('/', $path, 2)[0];
        if (in_array($first, self::PASSTHROUGH, true)) {
            return false;
        }
        $last = basename($path);

        // A file name (document, image, script) is served from disk, not by WordPress.
        return ! preg_match('/\.[a-z0-9]{2,5}$/i', $last) || $last === 'wp-sitemap.xml' || str_starts_with($last, 'wp-sitemap-');
    }
}
