<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme\Season;

use DateTimeImmutable;
use JsonSerializable;

final class Season implements JsonSerializable
{
    private const REGISTRATION_STATUSES = ['hidden', 'coming-soon', 'open', 'full', 'closed'];
    private static ?self $current = null;
    private static ?string $configurationPath = null;

    private function __construct(private array $data)
    {
    }

    public static function configure(string $configurationPath): void
    {
        self::$configurationPath = $configurationPath;
        self::$current = null;
    }

    public static function current(): self
    {
        if (self::$current instanceof self) {
            return self::$current;
        }

        try {
            if (! self::$configurationPath || ! is_readable(self::$configurationPath)) {
                throw new \RuntimeException('Season configuration is not readable.');
            }
            $data = json_decode((string) file_get_contents(self::$configurationPath), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($data)) {
                throw new \UnexpectedValueException('Season configuration must contain an object.');
            }
            self::$current = self::fromArray($data);
        } catch (\Throwable $error) {
            error_log('[taborovajizda] Invalid season configuration: ' . $error->getMessage());
            self::$current = self::fallback();
        }

        return self::$current;
    }

    public static function fromArray(array $data): self
    {
        if (! isset($data['year']) || ! is_int($data['year']) || $data['year'] < 2000) {
            throw new \UnexpectedValueException('Season year must be an integer greater than 1999.');
        }

        foreach (['slug', 'title'] as $key) {
            if (! isset($data[$key]) || ! is_string($data[$key]) || trim($data[$key]) === '') {
                throw new \UnexpectedValueException(sprintf('Season field "%s" is required.', $key));
            }
        }

        foreach (['startDate', 'endDate'] as $key) {
            if (($data[$key] ?? null) !== null && ! self::isIsoDate($data[$key])) {
                throw new \UnexpectedValueException(sprintf('Season field "%s" must be an ISO date or null.', $key));
            }
        }

        if (
            is_string($data['startDate'] ?? null)
            && is_string($data['endDate'] ?? null)
            && $data['startDate'] > $data['endDate']
        ) {
            throw new \UnexpectedValueException('Season start date must not be after the end date.');
        }

        $status = $data['registration']['status'] ?? null;
        if (! in_array($status, self::REGISTRATION_STATUSES, true)) {
            throw new \UnexpectedValueException('Season registration status is invalid.');
        }

        $externalSeasonKey = $data['registration']['externalSeasonKey'] ?? null;
        if ($externalSeasonKey !== null && (! is_string($externalSeasonKey) || ! ctype_digit($externalSeasonKey))) {
            throw new \UnexpectedValueException('External season key must contain digits only or be null.');
        }

        $amount = $data['price']['amount'] ?? null;
        if ($amount !== null && (! is_int($amount) || $amount < 0)) {
            throw new \UnexpectedValueException('Season price amount must be a non-negative integer or null.');
        }

        return new self($data);
    }

    public static function fallback(): self
    {
        return new self([
            'year' => (int) gmdate('Y'),
            'slug' => 'season-unavailable',
            'title' => __('Táborová jízda', 'taborovajizda'),
            'startDate' => null,
            'endDate' => null,
            'price' => ['amount' => null, 'currency' => 'CZK'],
            'registration' => ['status' => 'hidden', 'externalSeasonKey' => null],
            'assets' => ['hero' => null, 'logo' => 'web_images/logo_color.png'],
        ]);
    }

    public function year(): int
    {
        return $this->data['year'];
    }

    public function slug(): string
    {
        return $this->data['slug'];
    }

    public function title(): string
    {
        return $this->data['title'];
    }

    public function startDate(): ?DateTimeImmutable
    {
        return $this->dateValue('startDate');
    }

    public function endDate(): ?DateTimeImmutable
    {
        return $this->dateValue('endDate');
    }

    public function formattedDateRange(): string
    {
        $start = $this->startDate();
        $end = $this->endDate();

        if (! $start || ! $end) {
            return __('Termín připravujeme', 'taborovajizda');
        }

        return sprintf('%s. %s. - %s. %s. %d', $start->format('j'), $start->format('n'), $end->format('j'), $end->format('n'), $end->format('Y'));
    }

    public function registrationStatus(): string
    {
        return $this->data['registration']['status'];
    }

    public function externalSeasonKey(): ?string
    {
        $value = $this->data['registration']['externalSeasonKey'] ?? null;
        return is_string($value) && $value !== '' ? $value : null;
    }

    public function asset(string $name): ?string
    {
        $value = $this->data['assets'][$name] ?? null;
        return is_string($value) && $value !== '' ? $value : null;
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }

    private function dateValue(string $key): ?DateTimeImmutable
    {
        $value = $this->data[$key] ?? null;
        return is_string($value) ? DateTimeImmutable::createFromFormat('!Y-m-d', $value) ?: null : null;
    }

    private static function isIsoDate(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value;
    }
}
