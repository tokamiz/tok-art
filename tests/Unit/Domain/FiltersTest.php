<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Filters;
use App\Domain\Model\Block\GalleryBlock;
use App\Domain\Model\Block\ImageBlock;
use App\Domain\Model\Block\TextBlock;
use App\Domain\Model\Entry;
use PHPUnit\Framework\TestCase;

final class FiltersTest extends TestCase
{
    /** @return list<Entry> */
    private static function entries(): array
    {
        return [
            new Entry('fjorde', 'Fjorde', tripId: 'norwegen', topicIds: ['berge']),
            new Entry('oslo', 'Oslo', tripId: 'norwegen'),
            new Entry('alpen', 'Alpen', tripId: 'schweiz', topicIds: ['berge']),
            new Entry('kindheit', 'Kindheit', topicIds: ['erinnerungen']),
            new Entry('notiz', 'Notiz'),
        ];
    }

    /**
     * @param list<Entry> $entries
     *
     * @return list<string>
     */
    private static function titles(array $entries): array
    {
        return array_map(static fn (Entry $e): string => $e->title, $entries);
    }

    public function testByTripFindetNurEintraegeDerReise(): void
    {
        self::assertSame(['Fjorde', 'Oslo'], self::titles(Filters::apply(self::entries(), Filters::byTrip('norwegen'))));
    }

    public function testByTopicFindetNurEintraegeDesThemas(): void
    {
        self::assertSame(['Fjorde', 'Alpen'], self::titles(Filters::apply(self::entries(), Filters::byTopic('berge'))));
    }

    public function testWithoutTripAndTopicFindetLoseEintraege(): void
    {
        self::assertSame(['Notiz'], self::titles(Filters::apply(self::entries(), Filters::withoutTripAndTopic())));
    }

    public function testAllOfKombiniertFilter(): void
    {
        $filter = Filters::allOf(Filters::byTrip('norwegen'), Filters::byTopic('berge'));

        self::assertSame(['Fjorde'], self::titles(Filters::apply(self::entries(), $filter)));
    }

    public function testAllOfOhneFilterLiefertAlles(): void
    {
        self::assertCount(5, Filters::apply(self::entries(), Filters::allOf()));
    }

    public function testUsesMediaFindetBildUndGalerieAberKeinenText(): void
    {
        $entries = [
            new Entry('a', 'Bild', blocks: [new ImageBlock('b1', 'fjord')]),
            new Entry('b', 'Galerie', blocks: [new GalleryBlock('b1', ['oslo', 'fjord'])]),
            // Falle: Das Wort steht nur im Text, ein Medium ist es nicht.
            new Entry('c', 'Nur Text', blocks: [new TextBlock('b1', 'fjord')]),
        ];

        self::assertSame(['Bild', 'Galerie'], self::titles(Filters::apply($entries, Filters::usesMedia('fjord'))));
    }
}
