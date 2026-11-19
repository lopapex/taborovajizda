<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Registrations.php';

use TaborovaJizda\Theme\Registrations;

$genders = [
    ['145815/1234', 'holka'],
    ['1458151234', 'holka'],
    ['147815/1234', 'holka'],
    ['140815/1234', 'kluk'],
    ['142815/1234', 'kluk'],
    ['141315/1234', null],
    ['12345', null],
    ['', null],
];
foreach ($genders as [$personalId, $expected]) {
    if (Registrations::gender($personalId) !== $expected) {
        fwrite(STDERR, "Unexpected gender for personal ID: $personalId\n");
        exit(1);
    }
}

$camp = new DateTimeImmutable('2026-08-15');
$ages = [
    ['2016-08-15', 10],
    ['2016-08-16', 9],
    ['2019-01-01', 7],
    ['2027-01-01', null],
];
foreach ($ages as [$birth, $expected]) {
    if (Registrations::ageAt(new DateTimeImmutable($birth), $camp) !== $expected) {
        fwrite(STDERR, "Unexpected age for birth date: $birth\n");
        exit(1);
    }
}
if (Registrations::ageAt(null, $camp) !== null) {
    fwrite(STDERR, "Missing birth date must have no age\n");
    exit(1);
}

$phones = [
    ['+420777111222', '777 111 222'],
    ['00420777111222', '777 111 222'],
    ['777111222', '777 111 222'],
    ['777 111 222', '777 111 222'],
    ['+4915112345678', '+4915112345678'],
    ['', ''],
];
foreach ($phones as [$phone, $expected]) {
    if (Registrations::phone($phone) !== $expected) {
        fwrite(STDERR, "Unexpected phone format for: $phone\n");
        exit(1);
    }
}

fwrite(STDOUT, "Registrations tests passed.\n");
