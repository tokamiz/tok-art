<?php

declare(strict_types=1);

namespace App\Contact;

use App\Domain\Contact\ContactMessage;

final readonly class ContactResult
{
    /**
     * @param array<'name'|'email'|'message', string> $errors
     */
    public function __construct(
        public ContactMessage $message,
        public array $errors,
    ) {
    }

    public function isAccepted(): bool
    {
        return [] === $this->errors;
    }
}
