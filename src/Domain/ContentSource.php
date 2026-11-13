<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Model\Entry;
use App\Domain\Model\Keyword;
use App\Domain\Model\MediaItem;
use App\Domain\Model\Topic;
use App\Domain\Model\Trip;

/**
 * Liefert die Inhalte der App, unabhängig davon, woher sie kommen.
 * Im PoC: JSON-Dateien (App\Content\JsonContentSource). Später z. B. Strapi oder eine Datenbank.
 */
interface ContentSource
{
    /** @return list<Trip> */
    public function trips(): array;

    /** @return list<Topic> */
    public function topics(): array;

    /** @return list<Entry> */
    public function entries(): array;

    /** @return list<MediaItem> */
    public function mediaItems(): array;

    /** @return list<Keyword> */
    public function keywords(): array;
}
