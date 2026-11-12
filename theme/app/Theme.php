<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme;

use TaborovaJizda\Theme\Forms\Forms;
use TaborovaJizda\Theme\Season\Season;
use TaborovaJizda\Theme\Season\SeasonArchive;

final class Theme
{
    private static bool $booted = false;
    private static string $root;

    public static function boot(string $root): void
    {
        if (self::$booted) {
            return;
        }

        self::$booted = true;
        self::$root = $root;
        Season::configure($root . '/config/season.json');
        SeasonArchive::configure($root . '/config/archive-seasons.json');

        add_action('after_setup_theme', [self::class, 'setup']);
        add_action('init', [self::class, 'registerSeasonTaxonomy']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueueAssets']);
        add_action('wp_head', [self::class, 'preloadHeroImage'], 1);
        add_action('init', [self::class, 'trimFrontend']);
        // Homepage content iframes (maps etc.) live in closed dialogs and tabs;
        // never let WordPress treat them as above-the-fold and load them eagerly.
        add_filter('wp_iframe_tag_add_loading_attr', static fn ($value) => is_front_page() ? 'lazy' : $value);

        if (wp_get_environment_type() !== 'production') {
            add_filter('pre_wp_mail', static fn ($return) => $return ?? true);
        }

        (new Forms())->register();
        (new YouTubeEmbeds())->register();
        (new ContactLinks())->register();
        (new PostContentEndpoint())->register();
        (new IndexPermalinks())->register();
        (new GoogleGalleries())->register();
    }

    public static function setup(): void
    {
        add_theme_support('automatic-feed-links');
        add_theme_support('post-thumbnails');
        add_theme_support('align-wide');
        add_theme_support('editor-styles');
        add_theme_support('title-tag');
    }

    public static function registerSeasonTaxonomy(): void
    {
        register_taxonomy('rocnik', ['post'], [
            'labels' => [
                'name' => 'Ročníky',
                'singular_name' => 'Ročník',
            ],
            'public' => true,
            'hierarchical' => false,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'rocnik'],
        ]);
    }

    /**
     * The hero artwork is a CSS background, so the browser would only discover it
     * after the stylesheet is parsed. Preloading it lets the LCP image start
     * downloading in parallel with the CSS.
     */
    public static function preloadHeroImage(): void
    {
        if (! is_front_page()) {
            return;
        }

        $file = self::manifest()['resources/assets/season/hero.jpg']['file'] ?? null;
        if (! is_string($file) || $file === '') {
            return;
        }

        printf(
            '<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n",
            esc_url(get_theme_file_uri('/public/build/' . ltrim($file, '/')))
        );
    }

    private static function manifest(): array
    {
        static $manifest = null;
        if (is_array($manifest)) {
            return $manifest;
        }

        $manifest = [];
        $manifestPath = self::$root . '/public/build/manifest.json';
        if (! is_readable($manifestPath)) {
            return $manifest;
        }

        try {
            $decoded = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
            $manifest = is_array($decoded) ? $decoded : [];
        } catch (\Throwable $error) {
            error_log('[taborovajizda] Unable to load build manifest: ' . $error->getMessage());
        }

        return $manifest;
    }

    /**
     * Frontend weight: the WordPress emoji loader (and its s.w.org requests) is
     * not needed, modern systems render emoji natively.
     */
    public static function trimFrontend(): void
    {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        add_filter('emoji_svg_url', '__return_false');
    }

    public static function enqueueAssets(): void
    {
        $manifest = self::manifest();
        if ($manifest === []) {
            return;
        }

        try {
            $entry = $manifest['resources/ts/app.ts'] ?? null;
            if (! is_array($entry) || empty($entry['file'])) {
                return;
            }

            foreach (($entry['css'] ?? []) as $index => $stylesheet) {
                wp_enqueue_style('taborovajizda-' . $index, get_theme_file_uri('/public/build/' . ltrim($stylesheet, '/')), [], null);
            }

            wp_enqueue_script('taborovajizda-app', get_theme_file_uri('/public/build/' . ltrim($entry['file'], '/')), [], null, true);
            wp_script_add_data('taborovajizda-app', 'type', 'module');
        } catch (\Throwable $error) {
            error_log('[taborovajizda] Unable to load build manifest: ' . $error->getMessage());
        }
    }
}
