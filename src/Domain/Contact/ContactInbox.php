<?php

declare(strict_types=1);

namespace App\Domain\Contact;

/**
 * Wohin gültige Kontaktnachrichten gehen. Im PoC: Dateien in var/contact/.
 * Später z. B. E-Mail an das eigene Postfach oder Strapi.
 */
interface ContactInbox
{
    /** Speichert die Nachricht und liefert ihre ID. */
    public function save(ContactMessage $message): string;
}
