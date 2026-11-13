<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Model\Entry;

/**
 * Filter für Einträge. Jeder Filter ist eine Funktion: Eintrag → gehört er dazu?
 *
 * Verwendung: array_filter($entries, Filters::byTrip('island'))
 */
final class Filters
{
    /** @return \Closure(Entry): bool */
    public static function byTrip(string $tripId): \Closure
    {
        return static fn (Entry $entry): bool => $entry->tripId === $tripId;
    }

    /** @return \Closure(Entry): bool */
    public static function byTopic(string $topicId): \Closure
    {
        return static fn (Entry $entry): bool => \in_array($topicId, $entry->topicIds, true);
    }

    /**
     * Einträge ohne Reise und ohne Thema (Gruppe „Ohne Thema").
     *
     * @return \Closure(Entry): bool
     */
    public static function withoutTripAndTopic(): \Closure
    {
        return static fn (Entry $entry): bool => null === $entry->tripId && [] === $entry->topicIds;
    }

    /**
     * Einträge, die ein bestimmtes Foto oder Video zeigen – egal in welchem Blocktyp.
     *
     * @return \Closure(Entry): bool
     */
    public static function usesMedia(string $mediaId): \Closure
    {
        return static function (Entry $entry) use ($mediaId): bool {
            foreach ($entry->blocks as $block) {
                if (\in_array($mediaId, $block->mediaIds(), true)) {
                    return true;
                }
            }

            return false;
        };
    }

    /**
     * Kombiniert mehrere Filter: alle müssen zutreffen. Ohne Filter trifft alles zu.
     *
     * @param \Closure(Entry): bool ...$filters
     *
     * @return \Closure(Entry): bool
     */
    public static function allOf(\Closure ...$filters): \Closure
    {
        return static function (Entry $entry) use ($filters): bool {
            foreach ($filters as $filter) {
                if (!$filter($entry)) {
                    return false;
                }
            }

            return true;
        };
    }

    /**
     * Wendet einen Filter an und liefert eine neu nummerierte Liste.
     *
     * @param list<Entry>           $entries
     * @param \Closure(Entry): bool $filter
     *
     * @return list<Entry>
     */
    public static function apply(array $entries, \Closure $filter): array
    {
        return array_values(array_filter($entries, $filter));
    }
}
