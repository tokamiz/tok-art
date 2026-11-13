<?php

declare(strict_types=1);

namespace App\Domain\Photo;

use App\Domain\Dates;
use App\Domain\Entries;
use App\Domain\Filters;
use App\Domain\Model\Entry;
use App\Domain\Model\MediaItem;

/**
 * Alle Fotos (ohne Videos) mit kombinierbaren, facettierten Filtern.
 *
 * Facettiert heißt: Jedes Auswahlfeld bietet nur Optionen an, die zusammen mit
 * den anderen Feldern mindestens ein Foto ergeben. So entsteht nie ein leeres Ergebnis.
 */
final readonly class PhotoGallery
{
    /**
     * @param list<PhotoWithEntry> $photos neueste zuerst
     */
    private function __construct(
        public array $photos,
    ) {
    }

    /**
     * @param list<MediaItem> $mediaItems
     * @param list<Entry>     $entries
     */
    public static function build(array $mediaItems, array $entries): self
    {
        $images = array_values(array_filter($mediaItems, static fn (MediaItem $m): bool => $m->isImage()));
        usort($images, static fn (MediaItem $a, MediaItem $b): int => Dates::compare($a->date, $b->date));

        return new self(array_map(
            static fn (MediaItem $photo): PhotoWithEntry => new PhotoWithEntry($photo, self::entryFor($photo->id, $entries)),
            $images,
        ));
    }

    /**
     * Der neueste Eintrag, in dem das Foto vorkommt.
     *
     * @param list<Entry> $entries
     */
    private static function entryFor(string $mediaId, array $entries): ?Entry
    {
        return Entries::sorted(Filters::apply($entries, Filters::usesMedia($mediaId)))[0] ?? null;
    }

    /** @return list<PhotoWithEntry> */
    public function visible(PhotoFilter $filter): array
    {
        return array_values(array_filter($this->photos, $filter->matches(...)));
    }

    /**
     * Reisen, die zu Thema, Jahr und Schlagwort mindestens ein Foto haben.
     */
    public function availableTrips(PhotoFilter $filter): ValueSet
    {
        return $this->collect($filter->without('trip'), static fn (PhotoWithEntry $p): array => null === $p->tripId() ? [] : [$p->tripId()]);
    }

    /**
     * Themen, die zu Reise, Jahr und Schlagwort mindestens ein Foto haben.
     */
    public function availableTopics(PhotoFilter $filter): ValueSet
    {
        return $this->collect($filter->without('topic'), static fn (PhotoWithEntry $p): array => $p->photo->topicIds);
    }

    /**
     * Jahre, die zu Reise, Thema und Schlagwort mindestens ein Foto haben.
     */
    public function availableYears(PhotoFilter $filter): ValueSet
    {
        return $this->collect($filter->without('year'), static fn (PhotoWithEntry $p): array => [PhotoFilter::yearKey($p->photo->date)]);
    }

    /**
     * Schlagwörter, die zu Reise, Thema und Jahr mindestens ein Foto haben.
     */
    public function availableKeywords(PhotoFilter $filter): ValueSet
    {
        return $this->collect($filter->without('keyword'), static fn (PhotoWithEntry $p): array => $p->photo->keywordIds);
    }

    /**
     * Alle Jahre, in denen es Fotos gibt: neueste zuerst, „Ohne Datum" zuletzt.
     * Die Reihenfolge ergibt sich aus der Sortierung der Fotos.
     *
     * @return list<string>
     */
    public function years(): array
    {
        return (new ValueSet(array_map(
            static fn (PhotoWithEntry $p): string => PhotoFilter::yearKey($p->photo->date),
            $this->photos,
        )))->values();
    }

    /**
     * Sammelt die Werte aller Fotos, die zur Auswahl passen.
     *
     * @param \Closure(PhotoWithEntry): list<string> $values
     */
    private function collect(PhotoFilter $filter, \Closure $values): ValueSet
    {
        $collected = [];
        foreach ($this->visible($filter) as $item) {
            array_push($collected, ...$values($item));
        }

        return new ValueSet($collected);
    }
}
