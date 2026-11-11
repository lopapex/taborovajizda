<?php
/**
 * Theme bootstrap.
 *
 * @package TaborovaJizda
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

spl_autoload_register(
    static function (string $class): void {
        $prefix = 'TaborovaJizda\\Theme\\';

        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $path = __DIR__ . '/app/' . str_replace('\\', '/', $relative) . '.php';

        if (is_readable($path)) {
            require_once $path;
        }
    }
);

TaborovaJizda\Theme\Theme::boot(__DIR__);

/**
 * Return the active season configuration to templates.
 */
function taborovajizda_season(): TaborovaJizda\Theme\Season\Season
{
    return TaborovaJizda\Theme\Season\Season::current();
}

/**
 * Return verified historical camp seasons for archive templates.
 */
function taborovajizda_season_archive(?int $year = null): array
{
    return TaborovaJizda\Theme\Season\SeasonArchive::forYear($year);
}
