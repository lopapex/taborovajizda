<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme\Season;

final class SeasonRepository
{
    private static ?self $instance = null;
    private static ?string $configurationPath = null;
    private ?Season $season = null;

    public static function configure(string $configurationPath): void
    {
        self::$configurationPath = $configurationPath;
        self::$instance = null;
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function current(): Season
    {
        if ($this->season instanceof Season) {
            return $this->season;
        }

        try {
            if (! self::$configurationPath || ! is_readable(self::$configurationPath)) {
                throw new \RuntimeException('Season configuration is not readable.');
            }

            $decoded = json_decode((string) file_get_contents(self::$configurationPath), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($decoded)) {
                throw new \UnexpectedValueException('Season configuration must contain an object.');
            }

            $this->season = Season::fromArray($decoded);
        } catch (\Throwable $error) {
            error_log('[taborovajizda] Invalid season configuration: ' . $error->getMessage());
            $this->season = Season::fallback();
        }

        return $this->season;
    }
}
