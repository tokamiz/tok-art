<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Model\Block\GalleryBlock;
use App\Domain\Model\Block\ImageBlock;
use App\Domain\Model\Block\VideoBlock;
use App\Domain\Model\Entry;
use App\Domain\Model\MediaItem;
use App\Domain\Model\MediaKind;
use App\Domain\Photo\PhotoFilter;
use App\Domain\Photo\PhotoGallery;
use App\Domain\Photo\PhotoWithEntry;
use PHPUnit\Framework\TestCase;

final class PhotoGalleryTest extends TestCase
{
    private static function gallery(): PhotoGallery
    {
        $media = [
            new MediaItem('rom', MediaKind::Image, '/media/rom.svg', '2018-05', 'Rom', ['kultur'], ['museum', 'kaffee']),
            new MediaItem('dolomiten', MediaKind::Image, '/media/dolomiten.svg', '2018-05', 'Dolomiten', ['berge']),
            new MediaItem('athen', MediaKind::Image, '/media/athen.svg', '2021-09-11', 'Athen', ['kultur'], ['theater']),
            new MediaItem('barcelona', MediaKind::Image, '/media/barcelona.svg', null, 'Barcelona', ['kultur']),
            new MediaItem('lose', MediaKind::Image, '/media/lose.svg', '2024', null, ['gedanken']),
            new MediaItem('video', MediaKind::Video, '/media/video.mp4', '2024', null, ['berge']),
        ];
        $entries = [
            new Entry('italien', 'Italien', '2018-05', 'italien', blocks: [new GalleryBlock('b1', ['rom', 'dolomiten'])]),
            new Entry('athen-alt', 'Athen früher', '2021-09-01', 'griechenland', blocks: [new ImageBlock('b1', 'athen')]),
            new Entry('athen-neu', 'Athen später', '2021-09-20', 'griechenland', blocks: [new ImageBlock('b1', 'athen')]),
            new Entry('barcelona', 'Barcelona', null, 'spanien', blocks: [new ImageBlock('b1', 'barcelona')]),
            new Entry('film', 'Film', '2024', blocks: [new VideoBlock('b1', 'video')]),
        ];

        return PhotoGallery::build($media, $entries);
    }

    /**
     * @param list<PhotoWithEntry> $photos
     *
     * @return list<string>
     */
    private static function ids(array $photos): array
    {
        return array_map(static fn (PhotoWithEntry $p): string => $p->photo->id, $photos);
    }

    public function testZeigtNurFotosNeuesteZuerstOhneDatumAmEnde(): void
    {
        // Video fehlt; Rom und Dolomiten haben dasselbe Datum und behalten ihre Reihenfolge
        self::assertSame(['lose', 'athen', 'rom', 'dolomiten', 'barcelona'], self::ids(self::gallery()->photos));
    }

    public function testVerbindetJedesFotoMitDemNeuestenEintrag(): void
    {
        $byId = [];
        foreach (self::gallery()->photos as $item) {
            $byId[$item->photo->id] = $item;
        }

        self::assertSame('athen-neu', $byId['athen']->entry?->id);
        self::assertSame('griechenland', $byId['athen']->tripId());
        self::assertNull($byId['lose']->entry);
    }

    public function testFilterSindKombinierbar(): void
    {
        $gallery = self::gallery();

        self::assertSame(['rom', 'dolomiten'], self::ids($gallery->visible(new PhotoFilter(trip: 'italien'))));
        self::assertSame(['rom'], self::ids($gallery->visible(new PhotoFilter(trip: 'italien', keyword: 'kaffee'))));
        self::assertSame(['barcelona'], self::ids($gallery->visible(new PhotoFilter(year: PhotoFilter::NO_DATE))));
        self::assertSame([], self::ids($gallery->visible(new PhotoFilter(trip: 'spanien', keyword: 'theater'))));
    }

    public function testFacettenSchraenkenNurDurchDieAnderenFelderEin(): void
    {
        $gallery = self::gallery();
        $filter = new PhotoFilter(trip: 'italien', year: '2018');

        // Das Reisefeld ignoriert die eigene Auswahl: andere Reisen bleiben wählbar, sofern sie 2018 Fotos haben.
        self::assertSame(['italien'], $gallery->availableTrips($filter)->values());
        // Das Jahresfeld ignoriert die eigene Auswahl: Wechsel auf ein anderes Jahr von Italien bliebe möglich.
        self::assertSame(['2018'], $gallery->availableYears($filter)->values());
        self::assertSame(['kultur', 'berge'], $gallery->availableTopics($filter)->values());
        self::assertSame(['museum', 'kaffee'], $gallery->availableKeywords($filter)->values());
    }

    public function testOhneAuswahlIstAllesWaehlbarWasEsGibt(): void
    {
        $gallery = self::gallery();
        $filter = new PhotoFilter();

        self::assertFalse($filter->isActive());
        self::assertSame(['griechenland', 'italien', 'spanien'], $gallery->availableTrips($filter)->values());
        self::assertSame(['2024', '2021', '2018', PhotoFilter::NO_DATE], $gallery->years());
    }

    public function testWithoutLeertGenauEinFeld(): void
    {
        $filter = new PhotoFilter('italien', 'kultur', '2018', 'museum');

        self::assertEquals(new PhotoFilter('', 'kultur', '2018', 'museum'), $filter->without('trip'));
        self::assertEquals(new PhotoFilter('italien', 'kultur', '2018', ''), $filter->without('keyword'));
    }
}
