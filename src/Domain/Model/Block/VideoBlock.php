<?php

declare(strict_types=1);

namespace App\Domain\Model\Block;

final readonly class VideoBlock implements Block
{
    public function __construct(
        private string $id,
        public string $mediaId,
        public ?string $caption = null,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function type(): string
    {
        return 'video';
    }

    public function mediaIds(): array
    {
        return [$this->mediaId];
    }
}
