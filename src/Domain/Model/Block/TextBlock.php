<?php

declare(strict_types=1);

namespace App\Domain\Model\Block;

final readonly class TextBlock implements Block
{
    public function __construct(
        private string $id,
        public string $content,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function type(): string
    {
        return 'text';
    }

    public function mediaIds(): array
    {
        return [];
    }
}
