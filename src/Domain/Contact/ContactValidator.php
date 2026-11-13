<?php

declare(strict_types=1);

namespace App\Domain\Contact;

/**
 * Prüft eine Kontaktnachricht. Die Grenzen nutzt auch das Formular (maxlength).
 */
final class ContactValidator
{
    public const MAX_NAME_LENGTH = 100;
    public const MAX_MESSAGE_LENGTH = 2000;

    /**
     * Fehlermeldung je Feld; ein leeres Array bedeutet: alles in Ordnung.
     *
     * @return array<'name'|'email'|'message', string>
     */
    public static function validate(ContactMessage $input): array
    {
        $errors = [];

        if ('' === trim($input->name)) {
            $errors['name'] = 'Bitte gib deinen Namen an.';
        } elseif (mb_strlen($input->name) > self::MAX_NAME_LENGTH) {
            $errors['name'] = \sprintf('Der Name darf höchstens %d Zeichen lang sein.', self::MAX_NAME_LENGTH);
        }

        if ('' === trim($input->email)) {
            $errors['email'] = 'Bitte gib deine E-Mail-Adresse an.';
        } elseif (1 !== preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/u', $input->email)) {
            $errors['email'] = 'Bitte gib eine gültige E-Mail-Adresse an.';
        }

        if ('' === trim($input->message)) {
            $errors['message'] = 'Bitte gib eine Nachricht ein.';
        } elseif (mb_strlen($input->message) > self::MAX_MESSAGE_LENGTH) {
            $errors['message'] = \sprintf('Die Nachricht darf höchstens %d Zeichen lang sein.', self::MAX_MESSAGE_LENGTH);
        }

        return $errors;
    }
}
