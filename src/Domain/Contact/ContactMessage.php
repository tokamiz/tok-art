<?php

declare(strict_types=1);

namespace App\Domain\Contact;

final readonly class ContactMessage
{
    public function __construct(
        public string $name,
        public string $email,
        public string $message,
    ) {
    }

    /**
     * Baut eine Nachricht aus beliebigen Eingaben von außen.
     * Alles wird zu Text, Leerzeichen am Rand fallen weg. Geprüft wird danach.
     *
     * @param array<array-key, mixed> $input
     */
    public static function fromInput(array $input): self
    {
        $text = static fn (mixed $value): string => \is_scalar($value) ? trim((string) $value) : '';

        return new self(
            $text($input['name'] ?? ''),
            $text($input['email'] ?? ''),
            $text($input['message'] ?? ''),
        );
    }
}
