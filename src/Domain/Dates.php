<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Daten im ISO-Format mit beliebiger Genauigkeit: "2016" (Jahr), "2018-05" (Monat), "2019-07-12" (Tag).
 * Ein Datum ist immer optional.
 */
final class Dates
{
    /**
     * Neueste zuerst, genaueres Datum vor ungenauerem, ohne Datum ans Ende.
     */
    public static function compare(?string $a, ?string $b): int
    {
        if (null === $a && null === $b) {
            return 0;
        }
        if (null === $a) {
            return 1;
        }
        if (null === $b) {
            return -1;
        }

        // "2019-07-12" > "2019-07" > "2019": absteigend sortiert steht das genauere Datum vorn.
        return strcmp($b, $a);
    }

    /**
     * Lesbares deutsches Datum: "2016", "Juli 2019", "12. Juli 2019"; ohne Datum ein leerer Text.
     */
    public static function format(?string $date): string
    {
        if (null === $date || '' === $date) {
            return '';
        }

        $parts = explode('-', $date);
        if (\count($parts) > 3) {
            throw new \InvalidArgumentException(\sprintf('Ungültiges Datum: "%s"', $date));
        }
        if (1 === \count($parts)) {
            return $parts[0];
        }

        $year = (int) $parts[0];
        $month = (int) $parts[1];
        $day = (int) ($parts[2] ?? 1);
        $pattern = 3 === \count($parts) ? 'd. MMMM y' : 'MMMM y';

        $formatter = new \IntlDateFormatter('de_DE', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, 'UTC', null, $pattern);
        $utc = new \DateTimeZone('UTC');
        $moment = (new \DateTimeImmutable('now', $utc))->setDate($year, $month, $day)->setTime(0, 0);

        return (string) $formatter->format($moment);
    }

    /**
     * Das Jahr eines Datums, z. B. "2019" aus "2019-07-12"; ohne Datum null.
     */
    public static function yearOf(?string $date): ?string
    {
        if (null === $date || '' === $date) {
            return null;
        }

        return explode('-', $date)[0];
    }
}
