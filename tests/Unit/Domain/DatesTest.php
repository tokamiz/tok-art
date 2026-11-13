<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Dates;
use PHPUnit\Framework\TestCase;

final class DatesTest extends TestCase
{
    public function testCompareSortiertNeuesteZuerstGenauerVorUngenauerOhneDatumAmEnde(): void
    {
        $dates = ['2018', null, '2019-07', '2019-07-12', '2016'];

        usort($dates, Dates::compare(...));

        self::assertSame(['2019-07-12', '2019-07', '2018', '2016', null], $dates);
    }

    public function testCompareZweiFehlendeDatenSindGleich(): void
    {
        self::assertSame(0, Dates::compare(null, null));
    }

    public function testFormatZeigtJahrMonatUndTag(): void
    {
        self::assertSame('2016', Dates::format('2016'));
        self::assertSame('Juli 2019', Dates::format('2019-07'));
        self::assertSame('12. Juli 2019', Dates::format('2019-07-12'));
    }

    public function testFormatOhneDatumLiefertLeerenText(): void
    {
        self::assertSame('', Dates::format(null));
        self::assertSame('', Dates::format(''));
    }

    public function testFormatLehntZuVieleTeileAb(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Dates::format('2019-07-12-01');
    }

    public function testYearOfLiefertDasJahrBeiJederGenauigkeit(): void
    {
        self::assertSame('2019', Dates::yearOf('2019-07-12'));
        self::assertSame('2018', Dates::yearOf('2018-05'));
        self::assertSame('2016', Dates::yearOf('2016'));
        self::assertNull(Dates::yearOf(null));
    }
}
