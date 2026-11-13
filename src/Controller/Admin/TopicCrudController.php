<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Topic;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/** @extends AbstractCrudController<Topic> */
final class TopicCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Topic::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Thema')
            ->setEntityLabelInPlural('Themen')
            ->setDefaultSort(['name' => 'ASC'])
            ->setHelp(Crud::PAGE_INDEX, 'Themen beschreiben, worum es in einem Eintrag geht (z. B. „Berge"). Beim Löschen verschwindet nur die Zuordnung, die Einträge bleiben.');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Name');
        yield IdField::new($pageName, 'berge');
    }
}
