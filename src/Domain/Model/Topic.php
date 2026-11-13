<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Ein Thema: worum es in einem Eintrag geht, z. B. „Berge".
 */
final readonly class Topic
{
    public function __construct(
        public string $id,
        public string $name,
    ) {
    }
}
