<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Ein Schlagwort: was auf einem Foto zu sehen ist, z. B. „Tier".
 */
final readonly class Keyword
{
    public function __construct(
        public string $id,
        public string $name,
    ) {
    }
}
