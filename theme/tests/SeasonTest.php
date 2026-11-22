<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Season/Season.php';

use TaborovaJizda\Theme\Season\Season;

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($actual !== $expected) {
        fwrite(STDERR, sprintf("%s Expected %s, got %s.\n", $message, var_export($expected, true), var_export($actual, true)));
        exit(1);
    }
}

function assertInvalidSeason(array $data, string $message): void
{
    try {
        Season::fromArray($data);
    } catch (UnexpectedValueException) {
        return;
    }

    fwrite(STDERR, $message . "\n");
    exit(1);
}

$valid = [
    'year' => 2027,
    'slug' => '2027-test',
    'title' => 'Testovací ročník',
    'contactEmail' => 'info@example.test',
    'startDate' => '2027-08-01',
    'endDate' => '2027-08-14',
    'location' => 'Testovací tábořiště',
    'ageRange' => ['min' => 7, 'max' => 17],
    'mapCoordinates' => ['lat' => 50.1, 'lon' => 15.2],
    'price' => ['amount' => 5000, 'currency' => 'CZK'],
    'registration' => [
        'status' => 'open',
        'remainingPlaces' => 3,
        'externalSeasonKey' => '42',
    ],
];

$season = Season::fromArray($valid);
assertSameValue(2027, $season->year(), 'Season year mapping failed.');
assertSameValue('42', $season->externalSeasonKey(), 'External season key mapping failed.');
assertSameValue('1. 8. - 14. 8. 2027', $season->formattedDateRange(), 'Date range formatting failed.');
assertSameValue('5' . "\u{00A0}" . '000' . "\u{00A0}" . 'Kč', $season->formattedPrice(), 'Price formatting failed.');
assertSameValue('7-17 let', $season->formattedAgeRange(), 'Age range formatting failed.');
assertSameValue(['lat' => 50.1, 'lon' => 15.2], $season->mapCoordinates(), 'Map coordinates mapping failed.');
assertSameValue(true, $season->acceptsRegistrations(new DateTimeImmutable('2027-08-01')), 'Open season should accept registrations.');
assertSameValue(false, $season->acceptsRegistrations(new DateTimeImmutable('2027-08-15')), 'Ended season should reject registrations.');

$invalid = $valid;
$invalid['registration']['status'] = 'unknown';
assertInvalidSeason($invalid, 'Invalid registration status was accepted.');

$invalid = $valid;
$invalid['startDate'] = '2027-13-40';
assertInvalidSeason($invalid, 'Invalid ISO date was accepted.');

$invalid = $valid;
$invalid['startDate'] = '2027-08-20';
assertInvalidSeason($invalid, 'Reversed season dates were accepted.');

$invalid = $valid;
$invalid['contactEmail'] = 'not-an-email';
assertInvalidSeason($invalid, 'Invalid contact e-mail was accepted.');

$invalid = $valid;
$invalid['ageRange'] = ['min' => 18, 'max' => 7];
assertInvalidSeason($invalid, 'Reversed age range was accepted.');

$invalid = $valid;
$invalid['mapCoordinates'] = ['lat' => 91, 'lon' => 15.2];
assertInvalidSeason($invalid, 'Out-of-range map coordinates were accepted.');

fwrite(STDOUT, "Season tests passed.\n");
