<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme;

use DateTimeImmutable;

/**
 * Prevents the database-configured T(-) Countdown widget from ever showing a
 * negative countdown: once its target time has passed, or when the target
 * cannot be read, the widget is not rendered at all. The neutral season info
 * widgets in the same sidebar stay visible.
 */
final class CountdownWidget
{
    private const ID_BASE = 'countdowntimer';

    public function register(): void
    {
        add_filter('widget_display_callback', [$this, 'filter'], 10, 3);
    }

    /**
     * @param array<string, mixed>|false $instance
     * @return array<string, mixed>|false
     */
    public function filter(array|false $instance, \WP_Widget $widget, array $args): array|false
    {
        if ($instance === false || strtolower($widget->id_base) !== self::ID_BASE) {
            return $instance;
        }

        $target = self::target($instance);
        if (! $target || $target <= new DateTimeImmutable('now', wp_timezone())) {
            return false;
        }

        return $instance;
    }

    /**
     * Mirrors the plugin's own fallbacks: `t`, then legacy `year`/`month`/`day`,
     * then legacy `date`, each combined with `hour`/`min`/`sec`.
     *
     * @param array<string, mixed> $instance
     */
    public static function target(array $instance): ?DateTimeImmutable
    {
        $time = sprintf(
            '%02d:%02d:%02d',
            (int) ($instance['hour'] ?? 0),
            (int) ($instance['min'] ?? 0),
            (int) ($instance['sec'] ?? 0)
        );

        $value = match (true) {
            ! empty($instance['t']) => (string) $instance['t'],
            ! empty($instance['year']) => sprintf('%04d-%02d-%02d %s', (int) $instance['year'], (int) ($instance['month'] ?? 0), (int) ($instance['day'] ?? 0), $time),
            ! empty($instance['date']) => $instance['date'] . ' ' . $time,
            default => null,
        };

        if ($value === null) {
            return null;
        }

        try {
            return new DateTimeImmutable(trim($value), wp_timezone());
        } catch (\Exception) {
            return null;
        }
    }
}
