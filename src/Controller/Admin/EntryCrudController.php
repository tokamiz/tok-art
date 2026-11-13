<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Entry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/** @extends AbstractCrudController<Entry> */
final class EntryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Entry::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Eintrag')
            ->setEntityLabelInPlural('Einträge')
            ->setDefaultSort(['date' => 'DESC'])
            ->setSearchFields(['id', 'title']);
    }

    public function configureAssets(Assets $assets): Assets
    {
        // Blockfelder je nach Art ein- und ausblenden
        return $assets->addHtmlContentToBody($this->renderView('admin/_block_fields.html.twig'));
    }

    public function configureActions(Actions $actions): Actions
    {
        // Direkt zur fertigen Seite springen
        $view = Action::new('view', 'Ansehen', 'fa fa-eye')
            ->linkToUrl(fn (Entry $entry): string => $this->generateUrl('entry_show', ['id' => $entry->getId()]))
            ->setHtmlAttributes(['target' => '_blank']);

        return $actions
            ->add(Crud::PAGE_INDEX, $view)
            ->add(Crud::PAGE_EDIT, $view);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addFieldset('Eintrag');
        yield TextField::new('title', 'Titel');
        yield TextField::new('date', 'Datum')
            ->setHelp('Optional und auch ungefähr: 2016, 2018-05 oder 2019-07-12')
            ->formatValue(static fn (?string $value): string => $value ?? '–');
        yield AssociationField::new('trip', 'Reise')->setHelp('Ohne Reise erscheint der Eintrag nur unter „Themen".');
        yield AssociationField::new('topics', 'Themen')->setFormTypeOption('by_reference', false);
        yield IdField::new($pageName, 'am-wasserfall');

        yield FormField::addFieldset('Blöcke')->setHelp('Text, Bild, Video oder Galerie – in der Reihenfolge der Nummern.');
        yield CollectionField::new('blocks', 'Blöcke')
            ->useEntryCrudForm(BlockCrudController::class)
            ->setFormTypeOption('by_reference', false)
            ->allowAdd()
            ->allowDelete()
            ->renderExpanded()
            ->onlyOnForms();
    }
}
