<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme\Forms;

final class PersonalIdValidator
{
    public function isValid(string $value): bool
    {
        if (! preg_match('#^\s*(\d\d)(\d\d)(\d\d)[ /]*(\d\d\d)(\d?)\s*$#', $value, $matches)) {
            return false;
        }
        [, $year, $month, $day, $extension, $checksum] = $matches;
        $year = (int) $year;
        $month = (int) $month;
        $day = (int) $day;
        if ($checksum === '') {
            $year += $year < 54 ? 1900 : 1800;
        } else {
            $modulo = (int) (($matches[1] . $matches[2] . $matches[3] . $extension) % 11);
            if (($modulo === 10 ? 0 : $modulo) !== (int) $checksum) {
                return false;
            }
            $year += $year < 54 ? 2000 : 1900;
        }
        if ($month > 70 && $year > 2003) {
            $month -= 70;
        } elseif ($month > 50) {
            $month -= 50;
        } elseif ($month > 20 && $year > 2003) {
            $month -= 20;
        }
        return checkdate($month, $day, $year);
    }
}
