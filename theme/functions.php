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
 * Debugging aid: show the registration CTA and form even when the season does
 * not accept registrations, so they can be reviewed and tested. Active only in
 * the `local` and `staging` environments, where form submissions are simulated;
 * disable with `define('TJ_DEBUG_REGISTRATION_CTA', false);` in wp-config.php.
 */
function taborovajizda_debug_registration_cta(): bool
{
    if (! in_array(wp_get_environment_type(), ['local', 'staging'], true)) {
        return false;
    }

    return ! defined('TJ_DEBUG_REGISTRATION_CTA') || TJ_DEBUG_REGISTRATION_CTA !== false;
}

/**
 * Official social profiles, verified against the production widgets on 11. 11. 2026.
 *
 * @return array<string, string> Label => URL.
 */
function taborovajizda_social_links(): array
{
    return [
        'YouTube' => 'https://www.youtube.com/@taborovajizda6412',
        'Instagram' => 'https://www.instagram.com/taborova.jizda/',
        'Facebook' => 'https://www.facebook.com/taborovajizda.cz',
    ];
}

/**
 * Inline SVG icon of a social service (core Social Icons block), or an empty string.
 */
function taborovajizda_social_icon(string $service): string
{
    return function_exists('block_core_social_link_get_icon') ? block_core_social_link_get_icon(strtolower($service)) : '';
}

/**
 * Return verified historical camp seasons for archive templates.
 */
function taborovajizda_season_archive(?int $year = null): array
{
    return TaborovaJizda\Theme\Season\SeasonArchive::forYear($year);
}
