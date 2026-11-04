<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Season/Season.php';

use TaborovaJizda\Theme\Season\Season;

$valid = [
    'year' => 2027,
    'slug' => '2027-test',
    'title' => 'Test',
    'startDate' => '2027-08-01',
    'endDate' => '2027-08-14',
    'price' => ['amount' => 5000, 'currency' => 'CZK'],
    'registration' => ['status' => 'open', 'externalSeasonKey' => '42'],
    'assets' => ['hero' => null, 'logo' => null],
];

$season = Season::fromArray($valid);
if ($season->year() !== 2027 || $season->externalSeasonKey() !== '42') {
    fwrite(STDERR, "Season value mapping failed.\n");
    exit(1);
}

$invalid = $valid;
$invalid['registration']['status'] = 'unknown';
try {
    Season::fromArray($invalid);
    fwrite(STDERR, "Invalid registration status was accepted.\n");
    exit(1);
} catch (UnexpectedValueException) {
    // Expected.
}

$invalid = $valid;
$invalid['startDate'] = '2027-13-40';
try {
    Season::fromArray($invalid);
    fwrite(STDERR, "Invalid ISO date was accepted.\n");
    exit(1);
} catch (UnexpectedValueException) {
    // Expected.
}

$invalid = $valid;
$invalid['startDate'] = '2027-08-20';
try {
    Season::fromArray($invalid);
    fwrite(STDERR, "Reversed season dates were accepted.\n");
    exit(1);
} catch (UnexpectedValueException) {
    // Expected.
}

fwrite(STDOUT, "Season tests passed.\n");
