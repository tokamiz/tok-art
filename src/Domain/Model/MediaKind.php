<?php

declare(strict_types=1);

namespace App\Domain\Model;

enum MediaKind: string
{
    case Image = 'image';
    case Video = 'video';
}
