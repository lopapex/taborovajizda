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
        add_action('widgets_init', [self::class, 'registerWidgets']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueueAssets']);
        add_action('wp_head', [self::class, 'preloadHeroImage'], 1);
        // Homepage content iframes (maps etc.) live in closed dialogs and tabs;
        // never let WordPress treat them as above-the-fold and load them eagerly.
        add_filter('wp_iframe_tag_add_loading_attr', static fn ($value) => is_front_page() ? 'lazy' : $value);
        add_filter('nav_menu_link_attributes', [self::class, 'menuLinkAttributes'], 10, 4);

        if (wp_get_environment_type() !== 'production') {
            add_filter('pre_wp_mail', static fn ($return) => $return ?? true);
        }

        (new Forms())->register();
        (new PostNotifications())->register();
        (new CountdownWidget())->register();
        (new YouTubeEmbeds())->register();
        (new ContactLinks())->register();
        (new PostContentEndpoint())->register();
    }

    public static function setup(): void
    {
        add_theme_support('automatic-feed-links');
        add_theme_support('post-thumbnails');
        add_theme_support('align-wide');
        add_theme_support('editor-styles');
        add_theme_support('title-tag');
        register_nav_menus([
            'menu_main' => 'Primary Menu',
            'menu_main_mobile' => 'Primary Menu Mobile',
        ]);
    }

    public static function registerWidgets(): void
    {
        register_sidebar([
            'name' => 'Boční panel',
            'id' => 'sidebar-widget-area',
            'before_widget' => '<section id="%1$s" class="widget %2$s tj:mb-6">',
            'after_widget' => '</section>',
            'before_title' => '<h2 class="widget_title tj:mb-3 tj:text-2xl">',
            'after_title' => '</h2>',
        ]);
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

    public static function menuLinkAttributes(array $attributes, object $item, object $args, int $depth): array
    {
        $fragments = ['menu_main' => '#menu', 'menu_main_mobile' => '#menu_mobile'];
        $location = $args->theme_location ?? '';
        $href = $attributes['href'] ?? '';
        if (isset($fragments[$location]) && is_string($href) && $href !== '' && $href !== '#') {
            $attributes['href'] = preg_replace('/#.*$/', '', $href) . $fragments[$location];
        }
        return $attributes;
    }
}
