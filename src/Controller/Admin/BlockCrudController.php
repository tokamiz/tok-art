<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Block;
use App\Entity\Media;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Formular eines Blocks innerhalb eines Eintrags (EntryCrudController, CollectionField).
 * Nicht im Menü: Blöcke werden immer über ihren Eintrag bearbeitet.
 *
 * @extends AbstractCrudController<Block>
 */
final class BlockCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Block::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('position', 'Reihenfolge')->setColumns(4);
        yield ChoiceField::new('type', 'Art')->setChoices(Block::TYPES)->setColumns(8);
        yield TextareaField::new('content', 'Text')
            ->setHelp('Nur bei „Text". Zeilenumbrüche bleiben erhalten.')
            ->setNumOfRows(5)
            ->setColumns(12);
        yield AssociationField::new('media', 'Foto oder Video')
            ->setHelp('Nur bei „Bild" oder „Video".')
            ->setColumns(12);
        yield TextField::new('caption', 'Bildunterschrift')
            ->setHelp('Nur bei „Bild" oder „Video", optional.')
            ->setColumns(12);
        yield ChoiceField::new('galleryMediaIds', 'Fotos der Galerie')
            ->setChoices($this->photoChoices())
            ->allowMultipleChoices()
            ->setHelp('Nur bei „Galerie". Die Fotos erscheinen in der Reihenfolge dieser Liste.')
            ->setColumns(12);
    }

    /** @return array<string, string> Beschriftung → ID, nur Fotos */
    private function photoChoices(): array
    {
        $choices = [];
        foreach ($this->em->getRepository(Media::class)->findBy(['kind' => Media::KIND_IMAGE], ['id' => 'ASC']) as $media) {
            $choices[(string) $media] = (string) $media->getId();
        }

        return $choices;
    }
}
