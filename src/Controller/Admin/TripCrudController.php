<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Trip;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/** @extends AbstractCrudController<Trip> */
final class TripCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Trip::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Reise')
            ->setEntityLabelInPlural('Reisen')
            ->setDefaultSort(['startDate' => 'DESC'])
            ->setSearchFields(['id', 'title']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Titel');
        yield IdField::new($pageName, 'island-2024');
        yield TextField::new('startDate', 'Beginn')
            ->setHelp('Optional und auch ungefähr: 2016, 2018-05 oder 2019-07-12')
            ->formatValue(static fn (?string $value): string => $value ?? '–');
        yield TextField::new('endDate', 'Ende')
            ->setHelp('Optional')
            ->formatValue(static fn (?string $value): string => $value ?? '–');
    }
}
