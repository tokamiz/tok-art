<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Entries;
use App\Domain\Model\Entry;
use PHPUnit\Framework\TestCase;

final class EntriesTest extends TestCase
{
    public function testSortiertNachDatumUndBeiGleichstandAlphabetisch(): void
    {
        $entries = [
            new Entry('notiz', 'Notiz'),
            new Entry('rom', 'Rom', '2018-05'),
            new Entry('zug', 'Zug', '2019'),
            new Entry('aegypten', 'Ägypten', '2019'),
            new Entry('anfang', '„Anfang"'),
        ];

        $titles = array_map(static fn (Entry $e): string => $e->title, Entries::sorted($entries));

        // Umlaute richtig einsortiert, Satzzeichen ignoriert
        self::assertSame(['Ägypten', 'Zug', 'Rom', '„Anfang"', 'Notiz'], $titles);
    }

    public function testVeraendertDieUebergebeneListeNicht(): void
    {
        $entries = [new Entry('a', 'A', '2016'), new Entry('b', 'B', '2020')];

        Entries::sorted($entries);

        self::assertSame('a', $entries[0]->id);
    }
}
