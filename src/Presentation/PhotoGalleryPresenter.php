<?php

declare(strict_types=1);

namespace App\Presentation;

use App\Domain\ContentSource;
use App\Domain\Lookup;
use App\Domain\Model\Entry;
use App\Domain\Model\Topic;
use App\Domain\Model\Trip;
use App\Domain\Photo\PhotoFilter;
use App\Domain\Photo\PhotoGallery;
use App\Domain\Photo\PhotoWithEntry;
use App\Domain\Photo\ValueSet;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Bereitet die Fotogalerie für die Vue-Komponente auf: sichtbare Fotos und alle Filter mit ihren Optionen.
 *
 * Wird zweimal genutzt – als Startdaten in der Seite und als Antwort von GET /api/photos.
 * Die Logik (Facetten) bleibt in der Domain; hier entstehen nur Texte und Adressen.
 *
 * @phpstan-type Option array{value: string, label: string, available: bool}
 * @phpstan-type FilterField array{name: string, label: string, allLabel: string, value: string, options: list<Option>}
 * @phpstan-type Photo array{
 *     id: string,
 *     src: string,
 *     alt: string,
 *     place: ?string,
 *     entryUrl: ?string,
 *     trip: ?array{title: string, url: string},
 *     topics: list<array{name: string, url: string}>
 * }
 * @phpstan-type Gallery array{filters: list<FilterField>, active: bool, photos: list<Photo>}
 */
final readonly class PhotoGalleryPresenter
{
    /** Namen der Parameter in der Adresse: /photos?reise=italien&schlagwort=kaffee */
    public const QUERY = ['trip' => 'reise', 'topic' => 'thema', 'year' => 'jahr', 'keyword' => 'schlagwort'];

    public function __construct(
        private ContentSource $content,
        private UrlGeneratorInterface $urls,
        private Packages $assets,
    ) {
    }

    public static function filterFromRequest(Request $request): PhotoFilter
    {
        return new PhotoFilter(
            $request->query->getString(self::QUERY['trip']),
            $request->query->getString(self::QUERY['topic']),
            $request->query->getString(self::QUERY['year']),
            $request->query->getString(self::QUERY['keyword']),
        );
    }

    /** @return Gallery */
    public function present(PhotoFilter $filter): array
    {
        $gallery = PhotoGallery::build($this->content->mediaItems(), $this->content->entries());
        $trips = $this->content->trips();
        $topics = $this->content->topics();

        return [
            'filters' => [
                $this->field('trip', 'Reise', 'Alle Reisen', $filter->trip,
                    array_map(static fn (Trip $t): array => [$t->id, $t->title], $trips),
                    $gallery->availableTrips($filter)),
                $this->field('topic', 'Thema', 'Alle Themen', $filter->topic,
                    array_map(static fn (Topic $t): array => [$t->id, $t->name], $topics),
                    $gallery->availableTopics($filter)),
                $this->field('year', 'Jahr', 'Alle Jahre', $filter->year,
                    array_map(static fn (string $y): array => [$y, PhotoFilter::NO_DATE === $y ? 'Ohne Datum' : $y], $gallery->years()),
                    $gallery->availableYears($filter)),
                $this->field('keyword', 'Schlagwort', 'Alle Schlagwörter', $filter->keyword,
                    array_map(static fn ($k): array => [$k->id, $k->name], $this->content->keywords()),
                    $gallery->availableKeywords($filter)),
            ],
            'active' => $filter->isActive(),
            'photos' => array_map(
                fn (PhotoWithEntry $item): array => $this->photo($item, Lookup::byId($trips), Lookup::byId($topics)),
                $gallery->visible($filter),
            ),
        ];
    }

    /**
     * @param 'trip'|'topic'|'year'|'keyword' $key
     * @param list<array{string, string}>      $choices [Wert, Beschriftung]
     *
     * @return FilterField
     */
    private function field(string $key, string $label, string $allLabel, string $value, array $choices, ValueSet $available): array
    {
        return [
            'name' => self::QUERY[$key],
            'label' => $label,
            'allLabel' => $allLabel,
            'value' => $value,
            'options' => array_map(
                static fn (array $c): array => ['value' => $c[0], 'label' => $c[1], 'available' => $available->has($c[0])],
                $choices,
            ),
        ];
    }

    /**
     * @param array<string, Trip>  $trips
     * @param array<string, Topic> $topics
     *
     * @return Photo
     */
    private function photo(PhotoWithEntry $item, array $trips, array $topics): array
    {
        $photo = $item->photo;
        $trip = null === $item->tripId() ? null : ($trips[$item->tripId()] ?? null);

        return [
            'id' => $photo->id,
            'src' => $this->assets->getUrl(ltrim($photo->filePath, '/')),
            'alt' => $photo->place ?? '',
            'place' => $photo->place,
            'entryUrl' => $item->entry instanceof Entry ? $this->urls->generate('entry_show', ['id' => $item->entry->id]) : null,
            'trip' => null === $trip ? null : [
                'title' => $trip->title,
                'url' => $this->urls->generate('trip_show', ['id' => $trip->id]),
            ],
            'topics' => array_map(
                fn (Topic $t): array => ['name' => $t->name, 'url' => $this->urls->generate('topic_show', ['id' => $t->id])],
                Lookup::pick($topics, $photo->topicIds),
            ),
        ];
    }
}
