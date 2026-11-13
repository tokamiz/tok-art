<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Message;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Nachrichten aus dem Kontaktformular: lesen und löschen, nicht anlegen oder bearbeiten.
 *
 * @extends AbstractCrudController<Message>
 */
final class MessageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Message::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Nachricht')
            ->setEntityLabelInPlural('Nachrichten')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['name', 'email', 'message'])
            ->setHelp(Crud::PAGE_INDEX, 'Beantwortete Nachrichten bitte löschen – persönliche Daten nur so lange aufbewahren wie nötig.');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield DateTimeField::new('createdAt', 'Eingegangen')->setFormat('dd.MM.yyyy HH:mm');
        yield TextField::new('name', 'Name');
        yield EmailField::new('email', 'E-Mail');
        yield TextareaField::new('message', 'Nachricht')->setMaxLength(Crud::PAGE_INDEX === $pageName ? 80 : 2000);
    }
}
