<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Feld für die sprechende ID (erscheint in Adressen wie /trips/island).
 * Beim Anlegen frei wählbar, danach fest – sonst würden Links ins Leere zeigen.
 */
final class IdField
{
    public static function new(string $pageName, string $example): TextField
    {
        $field = TextField::new('id', 'Kennung (ID)')
            ->setHelp(\sprintf('Erscheint in der Adresse, z. B. „%s". Nur Kleinbuchstaben, Ziffern und Bindestriche; später nicht mehr änderbar.', $example));

        return Crud::PAGE_EDIT === $pageName ? $field->setDisabled() : $field;
    }
}
