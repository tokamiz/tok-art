<?php

declare(strict_types=1);

namespace App\Domain\Photo;

use App\Domain\Dates;

/**
 * Die aktuelle Auswahl der Fotos-Filter. Ein leerer Text bedeutet „Alle".
 */
final readonly class PhotoFilter
{
    /** Schlüssel für Fotos ohne Datum, damit „Ohne Datum" auswählbar ist. */
    public const NO_DATE = 'ohne-datum';

    public function __construct(
        public string $trip = '',
        public string $topic = '',
        public string $year = '',
        public string $keyword = '',
    ) {
    }

    public function isActive(): bool
    {
        return '' !== $this->trip || '' !== $this->topic || '' !== $this->year || '' !== $this->keyword;
    }

    /**
     * Dieselbe Auswahl ohne einen Filter. Grundlage der Facetten:
     * Ein Auswahlfeld wird nur von den anderen Feldern eingeschränkt, nie von sich selbst.
     */
    public function without(string $field): self
    {
        return new self(
            'trip' === $field ? '' : $this->trip,
            'topic' === $field ? '' : $this->topic,
            'year' === $field ? '' : $this->year,
            'keyword' === $field ? '' : $this->keyword,
        );
    }

    public function matches(PhotoWithEntry $item): bool
    {
        $photo = $item->photo;

        return ('' === $this->trip || $item->tripId() === $this->trip)
            && ('' === $this->topic || \in_array($this->topic, $photo->topicIds, true))
            && ('' === $this->year || self::yearKey($photo->date) === $this->year)
            && ('' === $this->keyword || \in_array($this->keyword, $photo->keywordIds, true));
    }

    /** Jahr eines Fotos als Auswahlwert; ohne Datum NO_DATE. */
    public static function yearKey(?string $date): string
    {
        return Dates::yearOf($date) ?? self::NO_DATE;
    }
}
