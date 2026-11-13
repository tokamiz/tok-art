<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Content\JsonContentSource;
use App\Entity\Block;
use App\Entity\Media;
use App\Media\ImageSanitizer;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FileField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Fotos und Videos. Hochgeladene Fotos werden sofort bereinigt (ImageSanitizer):
 * richtig gedreht, verkleinert, ohne Metadaten.
 *
 * @extends AbstractCrudController<Media>
 */
final class MediaCrudController extends AbstractCrudController
{
    public const UPLOAD_DIR = 'public/media';
    public const MAX_SIZE = '50M';

    public function __construct(
        private readonly ImageSanitizer $sanitizer,
        /** Beispielinhalte aus data/ – deren Dateien werden nie gelöscht */
        private readonly JsonContentSource $samples,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Media::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Foto oder Video')
            ->setEntityLabelInPlural('Fotos und Videos')
            ->setDefaultSort(['date' => 'DESC'])
            ->setSearchFields(['id', 'place', 'file'])
            ->setHelp(Crud::PAGE_NEW, 'Fotos (JPEG, PNG, WebP) werden beim Hochladen automatisch gedreht, auf höchstens 2000 Pixel verkleinert und von allen Metadaten (Ort, Kamera) befreit. Videos (MP4) bitte vorher ohne Ortsangaben exportieren.');
    }

    public function configureFields(string $pageName): iterable
    {
        $sanitizer = $this->sanitizer;

        yield TextField::new('file', 'Vorschau')
            ->setTemplatePath('admin/field/media_preview.html.twig')
            ->hideOnForm()
            ->setSortable(false);
        yield FileField::new('file', 'Datei')
            ->onlyOnForms()
            ->setUploadDir(self::UPLOAD_DIR)
            ->setBasePath('media')
            ->setUploadedFileNamePattern('[slug]-[randomhash].[extension]')
            ->mimeTypes('image/jpeg,image/png,image/webp,video/mp4', 'Erlaubt sind Fotos (JPEG, PNG, WebP) und Videos (MP4).')
            ->maxSize(self::MAX_SIZE)
            ->isDeletable(false)
            ->setRequired(Crud::PAGE_NEW === $pageName)
            // Nach dem Speichern der Datei: Foto bereinigen
            ->setFormTypeOption('upload_new', static function (UploadedFile $file, string $uploadDir, string $fileName) use ($sanitizer): void {
                $moved = $file->move($uploadDir, $fileName);
                $sanitizer->sanitize($moved->getPathname());
            });
        yield TextField::new('place', 'Ort')
            ->setHelp('Als Text, z. B. „Bergen, Norwegen" – keine Koordinaten.')
            ->formatValue(static fn (?string $value): string => $value ?? '–');
        yield TextField::new('date', 'Datum')
            ->setHelp('Optional und auch ungefähr: 2016, 2018-05 oder 2019-07-12')
            ->formatValue(static fn (?string $value): string => $value ?? '–');
        yield IdField::new($pageName, 'bergen-hafen');
        yield ChoiceField::new('kind', 'Art')
            ->setChoices(['Foto' => Media::KIND_IMAGE, 'Video' => Media::KIND_VIDEO])
            ->renderAsBadges([Media::KIND_IMAGE => 'info', Media::KIND_VIDEO => 'secondary'])
            ->hideOnForm();
        yield AssociationField::new('topics', 'Themen')->setFormTypeOption('by_reference', false);
        yield AssociationField::new('keywords', 'Schlagwörter')->setFormTypeOption('by_reference', false);
    }

    /**
     * Ein Medium, das noch in einem Eintrag steckt, lässt sich nicht löschen – sonst hätte der Eintrag eine Lücke.
     */
    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $usages = $this->usages($entityManager, (string) $entityInstance->getId());
        if ([] !== $usages) {
            $this->addFlash('danger', \sprintf('„%s" wird noch verwendet in: %s. Bitte zuerst dort entfernen.', $entityInstance->getId(), implode(', ', $usages)));

            return;
        }

        $file = (string) $entityInstance->getFile();
        parent::deleteEntity($entityManager, $entityInstance);

        // Datei mit löschen, wenn kein anderes Medium sie nutzt – außer den Beispielmedien aus data/media.json:
        // Die braucht die Testdatenbank, sie bleiben als Dateien erhalten.
        $path = $this->projectDir.'/'.self::UPLOAD_DIR.'/'.basename($file);
        if ('' !== $file && is_file($path) && !$this->isSampleFile($file)
            && 0 === $entityManager->getRepository(Media::class)->count(['file' => $file])) {
            unlink($path);
        }
    }

    private function isSampleFile(string $file): bool
    {
        foreach ($this->samples->mediaItems() as $item) {
            if (basename($item->filePath) === basename($file)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> Titel der Einträge, die das Medium zeigen */
    private function usages(EntityManagerInterface $em, string $mediaId): array
    {
        $titles = [];
        foreach ($em->getRepository(Block::class)->findAll() as $block) {
            if ($block->getMedia()?->getId() === $mediaId || \in_array($mediaId, $block->getGalleryMediaIds(), true)) {
                $titles[] = (string) $block->getEntry()?->getTitle();
            }
        }

        return array_values(array_unique($titles));
    }
}
