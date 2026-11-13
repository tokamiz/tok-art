<?php

declare(strict_types=1);

namespace App\Domain\Model\Block;

/**
 * Ein Baustein eines Eintrags. Neue Blocktypen implementieren dieses Interface
 * und bekommen eine eigene Vorlage in templates/blocks/.
 */
interface Block
{
    public function id(): string;

    /** Name der Vorlage, z. B. "text" → templates/blocks/_text.html.twig */
    public function type(): string;

    /**
     * Alle Medien, auf die der Block verweist. Grundlage für den Sprung von der Galerie zum Eintrag.
     *
     * @return list<string>
     */
    public function mediaIds(): array;
}
