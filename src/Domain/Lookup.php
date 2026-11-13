<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Nachschlage-Tabellen: einmal aufbauen, dann jedes Objekt direkt über seine ID finden.
 */
final class Lookup
{
    /**
     * @template T of object{id: string}
     *
     * @param iterable<T> $items
     *
     * @return array<string, T>
     */
    public static function byId(iterable $items): array
    {
        $map = [];
        foreach ($items as $item) {
            $map[$item->id] = $item;
        }

        return $map;
    }

    /**
     * Sucht die Objekte zu einer Liste von IDs. Unbekannte IDs werden übersprungen.
     *
     * @template T
     *
     * @param array<string, T> $map
     * @param list<string>     $ids
     *
     * @return list<T>
     */
    public static function pick(array $map, array $ids): array
    {
        $found = [];
        foreach ($ids as $id) {
            if (isset($map[$id])) {
                $found[] = $map[$id];
            }
        }

        return $found;
    }
}
