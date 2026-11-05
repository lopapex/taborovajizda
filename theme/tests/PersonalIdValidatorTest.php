<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Forms/PersonalIdValidator.php';

use TaborovaJizda\Theme\Forms\PersonalIdValidator;

$validator = new PersonalIdValidator();
$cases = [
    ['850101/0001', true],
    ['1201010008', true],
    ['535101/123', true],
    ['1201010009', false],
    ['991332/0000', false],
    ['not-an-id', false],
    ['', false],
];

foreach ($cases as [$value, $expected]) {
    if ($validator->isValid($value) !== $expected) {
        fwrite(STDERR, "Unexpected result for personal ID: $value\n");
        exit(1);
    }
}

fwrite(STDOUT, "Personal ID validator tests passed.\n");
