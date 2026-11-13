<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Keyword;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/** @extends AbstractCrudController<Keyword> */
final class KeywordCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Keyword::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Schlagwort')
            ->setEntityLabelInPlural('Schlagwörter')
            ->setDefaultSort(['name' => 'ASC'])
            ->setHelp(Crud::PAGE_INDEX, 'Schlagwörter beschreiben, was auf einem Foto zu sehen ist (z. B. „Tier") – im Unterschied zu Themen.');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Name');
        yield IdField::new($pageName, 'wasserfall');
    }
}
