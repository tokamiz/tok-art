<?php

declare(strict_types=1);

namespace App\Domain\Model\Block;

final readonly class GalleryBlock implements Block
{
    /**
     * @param list<string> $mediaIds
     */
    public function __construct(
        private string $id,
        private array $mediaIds,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function type(): string
    {
        return 'gallery';
    }

    public function mediaIds(): array
    {
        return $this->mediaIds;
    }
}
