<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme\Assets;

final class AssetManager
{
    public function __construct(private string $themeRoot)
    {
    }

    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(): void
    {
        $manifestPath = $this->themeRoot . '/public/build/manifest.json';
        if (! is_readable($manifestPath)) {
            if (current_user_can('manage_options')) {
                add_action('wp_footer', static function (): void {
                    echo '<!-- Táborová jízda: missing public/build. Run npm install and npm run build in theme/. -->';
                });
            }
            return;
        }

        try {
            $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
            $entry = $manifest['resources/ts/app.ts'] ?? null;
            if (! is_array($entry) || empty($entry['file'])) {
                return;
            }

            foreach (($entry['css'] ?? []) as $index => $stylesheet) {
                wp_enqueue_style(
                    'taborovajizda-build-' . $index,
                    get_theme_file_uri('/public/build/' . ltrim($stylesheet, '/')),
                    [],
                    null
                );
            }

            wp_enqueue_script(
                'taborovajizda-app',
                get_theme_file_uri('/public/build/' . ltrim($entry['file'], '/')),
                [],
                null,
                true
            );
            wp_script_add_data('taborovajizda-app', 'type', 'module');
        } catch (\Throwable $error) {
            error_log('[taborovajizda] Unable to load Vite manifest: ' . $error->getMessage());
        }
    }

}
