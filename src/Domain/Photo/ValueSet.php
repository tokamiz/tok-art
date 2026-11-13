<?php

declare(strict_types=1);

namespace App\Domain\Photo;

/**
 * Eine Menge von Texten ohne Wiederholungen, in der Reihenfolge des Hinzufügens (wie Set in JavaScript).
 *
 * Eigene Klasse statt Array-Schlüsseln, weil PHP Schlüssel wie "2018" stillschweigend in Zahlen umwandelt.
 */
final readonly class ValueSet
{
    /** @var list<string> */
    private array $values;

    /**
     * @param iterable<string> $values
     */
    public function __construct(iterable $values = [])
    {
        $unique = [];
        foreach ($values as $value) {
            if (!\in_array($value, $unique, true)) {
                $unique[] = $value;
            }
        }
        $this->values = $unique;
    }

    public function has(string $value): bool
    {
        return \in_array($value, $this->values, true);
    }

    /** @return list<string> */
    public function values(): array
    {
        return $this->values;
    }
}
