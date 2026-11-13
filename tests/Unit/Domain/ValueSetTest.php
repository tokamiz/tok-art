<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Photo\ValueSet;
use PHPUnit\Framework\TestCase;

final class ValueSetTest extends TestCase
{
    public function testEntferntWiederholungenUndBehaeltDieReihenfolge(): void
    {
        self::assertSame(['b', 'a'], (new ValueSet(['b', 'a', 'b']))->values());
    }

    public function testZahlenTexteBleibenTexte(): void
    {
        // In PHP-Arrays würde "2018" als Schlüssel zur Zahl 2018.
        $set = new ValueSet(['2018']);

        self::assertTrue($set->has('2018'));
        self::assertSame(['2018'], $set->values());
    }
}
