<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Model\Entry;

/**
 * Sortierung von Einträgen. Nutzt Dates – nie umgekehrt.
 */
final class Entries
{
    private static ?\Collator $collator = null;

    /**
     * Nach Datum (siehe Dates::compare), bei Gleichstand alphabetisch nach Titel.
     */
    public static function compare(Entry $a, Entry $b): int
    {
        $byDate = Dates::compare($a->date, $b->date);
        if (0 !== $byDate) {
            return $byDate;
        }

        return (int) self::collator()->compare($a->title, $b->title);
    }

    /**
     * Liefert eine sortierte Kopie; die übergebene Liste bleibt unverändert.
     *
     * @param list<Entry> $entries
     *
     * @return list<Entry>
     */
    public static function sorted(array $entries): array
    {
        usort($entries, [self::class, 'compare']);

        return $entries;
    }

    /** Deutsche Sortierung: Umlaute richtig, Satzzeichen ignoriert. */
    private static function collator(): \Collator
    {
        if (null === self::$collator) {
            self::$collator = new \Collator('de_DE');
            self::$collator->setAttribute(\Collator::ALTERNATE_HANDLING, \Collator::SHIFTED);
        }

        return self::$collator;
    }
}
