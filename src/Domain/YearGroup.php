<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Model\Entry;

/**
 * Eine Gruppe der Zeitleiste. Ohne Jahr ist es die Gruppe „Ohne Datum".
 */
final readonly class YearGroup
{
    /**
     * @param list<Entry> $entries
     */
    public function __construct(
        public ?string $year,
        public array $entries,
    ) {
    }
}
