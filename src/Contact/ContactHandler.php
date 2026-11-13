<?php

declare(strict_types=1);

namespace App\Contact;

use App\Domain\Contact\ContactInbox;
use App\Domain\Contact\ContactMessage;
use App\Domain\Contact\ContactValidator;

/**
 * Nimmt eine Kontaktnachricht entgegen: normalisieren, Spam aussortieren, prüfen, speichern.
 * Genutzt vom HTML-Formular (ContactController) und von der API (ContactApiController).
 */
final readonly class ContactHandler
{
    /** Unsichtbares Feld im Formular: Menschen lassen es leer, Bots füllen es aus. */
    public const HONEYPOT = 'website';

    public function __construct(
        private ContactInbox $inbox,
    ) {
    }

    /**
     * @param array<array-key, mixed> $input
     */
    public function handle(array $input): ContactResult
    {
        $message = ContactMessage::fromInput($input);

        $honeypot = $input[self::HONEYPOT] ?? '';
        if (\is_string($honeypot) && '' !== $honeypot) {
            // Still verwerfen, aber wie Erfolg antworten – der Bot soll nichts merken.
            return new ContactResult($message, []);
        }

        $errors = ContactValidator::validate($message);
        if ([] === $errors) {
            $this->inbox->save($message);
        }

        return new ContactResult($message, $errors);
    }
}
