<?php

declare(strict_types=1);

namespace App\Domain\Photo;

use App\Domain\Model\Entry;
use App\Domain\Model\MediaItem;

/**
 * Ein Foto zusammen mit dem Eintrag, in dem es vorkommt (falls es einen gibt).
 * Die Reise eines Fotos ergibt sich aus diesem Eintrag.
 */
final readonly class PhotoWithEntry
{
    public function __construct(
        public MediaItem $photo,
        public ?Entry $entry = null,
    ) {
    }

    public function tripId(): ?string
    {
        return $this->entry?->tripId;
    }
}
