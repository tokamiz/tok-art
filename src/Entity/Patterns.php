<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Gemeinsame Prüfmuster für die Formulare der Verwaltung.
 */
final class Patterns
{
    /** IDs erscheinen in Adressen (/trips/island): Kleinbuchstaben, Ziffern, Bindestriche. */
    public const ID = '/^[a-z0-9]+(-[a-z0-9]+)*$/';
    public const ID_MESSAGE = 'Nur Kleinbuchstaben, Ziffern und Bindestriche, z. B. "island-2024".';

    /** Datum mit beliebiger Genauigkeit: 2016, 2018-05 oder 2019-07-12. */
    public const DATE = '/^\d{4}(-(0[1-9]|1[0-2])(-(0[1-9]|[12]\d|3[01]))?)?$/';
    public const DATE_MESSAGE = 'Jahr, Monat oder Tag: z. B. 2016, 2018-05 oder 2019-07-12.';
}
