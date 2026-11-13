<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Ein Foto oder Video. Datum und Ort werden von Hand eingetragen, nie aus EXIF gelesen.
 */
final readonly class MediaItem
{
    /**
     * @param list<string> $topicIds
     * @param list<string> $keywordIds
     */
    public function __construct(
        public string $id,
        public MediaKind $kind,
        public string $filePath,
        public ?string $date = null,
        /** Ortsname als Text, z. B. "Bergen, Norwegen". Keine Koordinaten. */
        public ?string $place = null,
        public array $topicIds = [],
        /** Optional: Fotos ohne Schlagwort bleiben gültig. */
        public array $keywordIds = [],
    ) {
    }

    public function isImage(): bool
    {
        return MediaKind::Image === $this->kind;
    }
}
