<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Model\Entry;
use App\Domain\Timeline;
use App\Domain\YearGroup;
use PHPUnit\Framework\TestCase;

final class TimelineTest extends TestCase
{
    public function testGruppiertNachJahrNeuesteZuerstOhneDatumAmEnde(): void
    {
        // absichtlich durcheinander: die Funktion muss selbst sortieren
        $groups = Timeline::groupByYear([
            new Entry('rom', 'Rom', '2018-05'),
            new Entry('notiz', 'Notiz'),
            new Entry('helsinki', 'Helsinki', '2019-07-12'),
            new Entry('lappland', 'Lappland', '2019-07'),
        ]);

        $summary = array_map(
            static fn (YearGroup $g): array => [$g->year, array_map(static fn (Entry $e): string => $e->title, $g->entries)],
            $groups,
        );

        self::assertSame([
            ['2019', ['Helsinki', 'Lappland']],
            ['2018', ['Rom']],
            [null, ['Notiz']],
        ], $summary);
    }

    public function testLiefertLeereListeOhneEintraege(): void
    {
        self::assertSame([], Timeline::groupByYear([]));
    }
}
