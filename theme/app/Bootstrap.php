<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme;

use TaborovaJizda\Theme\Assets\AssetManager;
use TaborovaJizda\Theme\Forms\LegacyFormController;
use TaborovaJizda\Theme\Season\SeasonRepository;

final class Bootstrap
{
    private static bool $booted = false;

    public static function boot(string $themeRoot): void
    {
        if (self::$booted) {
            return;
        }

        self::$booted = true;
        SeasonRepository::configure($themeRoot . '/config/season.json');

        require_once $themeRoot . '/app/Legacy/functions.php';

        if (wp_get_environment_type() !== 'production') {
            add_filter('pre_wp_mail', static fn ($return) => $return ?? true);
        }

        (new AssetManager($themeRoot))->register();
        (new LegacyFormController($themeRoot))->register();
    }
}
