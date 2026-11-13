<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Model\Block\Block;

/**
 * Ein Tagebucheintrag: eine geordnete Liste von Blöcken.
 * Ohne Reise erscheint er nur im Tab „Themen".
 */
final readonly class Entry
{
    /**
     * @param list<string> $topicIds
     * @param list<Block>  $blocks
     */
    public function __construct(
        public string $id,
        public string $title,
        public ?string $date = null,
        public ?string $tripId = null,
        public array $topicIds = [],
        public array $blocks = [],
    ) {
    }
}
