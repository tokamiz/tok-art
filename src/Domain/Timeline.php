<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Model\Entry;

final class Timeline
{
    /**
     * Gruppiert Einträge nach Jahr: neueste zuerst, ohne Datum am Ende.
     *
     * @param list<Entry> $entries
     *
     * @return list<YearGroup>
     */
    public static function groupByYear(array $entries): array
    {
        /** @var array<string, list<Entry>> $byYear */
        $byYear = [];
        /** @var list<?string> $order */
        $order = [];

        foreach (Entries::sorted($entries) as $entry) {
            $year = Dates::yearOf($entry->date);
            $key = $year ?? '';
            if (!\array_key_exists($key, $byYear)) {
                $byYear[$key] = [];
                $order[] = $year;
            }
            $byYear[$key][] = $entry;
        }

        return array_map(
            static fn (?string $year): YearGroup => new YearGroup($year, $byYear[$year ?? '']),
            $order,
        );
    }
}
