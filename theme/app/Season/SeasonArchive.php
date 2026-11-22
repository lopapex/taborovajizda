<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme\Season;

final class SeasonArchive
{
    private static ?string $configurationPath = null;
    private static ?array $seasons = null;

    public static function configure(string $configurationPath): void
    {
        self::$configurationPath = $configurationPath;
        self::$seasons = null;
    }

    public static function all(): array
    {
        if (is_array(self::$seasons)) {
            return self::$seasons;
        }

        try {
            if (! self::$configurationPath || ! is_readable(self::$configurationPath)) {
                throw new \RuntimeException('Archive season configuration is not readable.');
            }

            $configuration = json_decode((string) file_get_contents(self::$configurationPath), true, 512, JSON_THROW_ON_ERROR);
            $seasons = $configuration['seasons'] ?? null;
            if (! is_array($seasons)) {
                throw new \UnexpectedValueException('Archive season configuration must contain a seasons array.');
            }

            self::$seasons = array_values(array_filter($seasons, [self::class, 'isValidSeason']));
        } catch (\Throwable $error) {
            error_log('[taborovajizda] Invalid archive season configuration: ' . $error->getMessage());
            self::$seasons = [];
        }

        return self::$seasons;
    }

    public static function years(): array
    {
        return array_map(static fn (array $season): int => $season['year'], self::all());
    }

    public static function forYear(?int $year): array
    {
        if ($year === null) {
            return self::all();
        }

        return array_values(array_filter(
            self::all(),
            static fn (array $season): bool => $season['year'] === $year
        ));
    }

    private static function isValidSeason(mixed $season): bool
    {
        return is_array($season)
            && is_int($season['year'] ?? null)
            && is_string($season['title'] ?? null)
            && trim($season['title']) !== ''
            && is_string($season['youtubeId'] ?? null)
            && preg_match('/^[A-Za-z0-9_-]{11}$/', $season['youtubeId']) === 1
            && in_array($season['thumbnail'] ?? null, ['maxresdefault', 'sddefault', 'hqdefault'], true)
            && (! isset($season['thumbnailZoom']) || (is_numeric($season['thumbnailZoom']) && $season['thumbnailZoom'] >= 1 && $season['thumbnailZoom'] <= 2))
            && (($season['categorySlug'] ?? null) === null || is_string($season['categorySlug']));
    }
}
