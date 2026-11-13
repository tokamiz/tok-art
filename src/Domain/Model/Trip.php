<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Eine Reise. Kernpunkt des Tagebuchs.
 */
final readonly class Trip
{
    public function __construct(
        public string $id,
        public string $title,
        /** Optional und auch ungefähr: "2016", "2018-05", "2019-07-08". */
        public ?string $startDate = null,
        public ?string $endDate = null,
    ) {
    }
}
