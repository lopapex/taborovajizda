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
 * Local debugging aid: show the registration CTA even when the season does not
 * accept registrations, so its placement can be reviewed. Never active outside
 * the `local` environment; disable locally with
 * `define('TJ_DEBUG_REGISTRATION_CTA', false);` in wp-config.php.
 */
function taborovajizda_debug_registration_cta(): bool
{
    if (wp_get_environment_type() !== 'local') {
        return false;
    }

    return ! defined('TJ_DEBUG_REGISTRATION_CTA') || TJ_DEBUG_REGISTRATION_CTA !== false;
}

/**
 * Return verified historical camp seasons for archive templates.
 */
function taborovajizda_season_archive(?int $year = null): array
{
    return TaborovaJizda\Theme\Season\SeasonArchive::forYear($year);
}
