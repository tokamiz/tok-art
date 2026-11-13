<?php

declare(strict_types=1);

namespace App\Domain\Model\Block;

final readonly class ImageBlock implements Block
{
    public function __construct(
        private string $id,
        public string $mediaId,
        /** Gehört zur Verwendung im Eintrag, nicht zum Foto selbst. */
        public ?string $caption = null,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function type(): string
    {
        return 'image';
    }

    public function mediaIds(): array
    {
        return [$this->mediaId];
    }
}
