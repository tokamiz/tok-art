<?php

declare(strict_types=1);

namespace App\Command;

use App\Content\JsonContentSource;
use App\Domain\Model\Block as DomainBlock;
use App\Entity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Übernimmt die Beispielinhalte aus data/*.json in die Datenbank.
 * Genutzt beim Einrichten und für die Testdatenbank.
 */
#[AsCommand(name: 'app:import-json', description: 'Inhalte aus data/*.json in die Datenbank übernehmen')]
final class ImportJsonCommand extends Command
{
    public function __construct(
        private readonly JsonContentSource $json,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('replace', null, InputOption::VALUE_NONE, 'Vorhandene Inhalte (nicht Nachrichten und Konten) vorher löschen');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->hasContent()) {
            if (!$input->getOption('replace')) {
                $io->error('Die Datenbank enthält bereits Inhalte. Mit --replace werden sie ersetzt.');

                return Command::FAILURE;
            }
            $this->purge();
        }

        $topics = [];
        foreach ($this->json->topics() as $t) {
            $this->em->persist($topics[$t->id] = (new Entity\Topic())->setId($t->id)->setName($t->name));
        }
        $keywords = [];
        foreach ($this->json->keywords() as $k) {
            $this->em->persist($keywords[$k->id] = (new Entity\Keyword())->setId($k->id)->setName($k->name));
        }
        $trips = [];
        foreach ($this->json->trips() as $t) {
            $this->em->persist($trips[$t->id] = (new Entity\Trip())->setId($t->id)->setTitle($t->title)->setStartDate($t->startDate)->setEndDate($t->endDate));
        }
        $media = [];
        foreach ($this->json->mediaItems() as $m) {
            $entity = (new Entity\Media())->setId($m->id)->setFile(basename($m->filePath))->setDate($m->date)->setPlace($m->place);
            array_map(static fn (string $id) => $entity->addTopic($topics[$id]), $m->topicIds);
            array_map(static fn (string $id) => $entity->addKeyword($keywords[$id]), $m->keywordIds);
            $this->em->persist($media[$m->id] = $entity);
        }
        foreach ($this->json->entries() as $e) {
            $entry = (new Entity\Entry())->setId($e->id)->setTitle($e->title)->setDate($e->date)
                ->setTrip(null === $e->tripId ? null : $trips[$e->tripId]);
            array_map(static fn (string $id) => $entry->addTopic($topics[$id]), $e->topicIds);
            foreach ($e->blocks as $position => $b) {
                $block = (new Entity\Block())->setPosition($position + 1)->setType($b->type());
                match (true) {
                    $b instanceof DomainBlock\TextBlock => $block->setContent($b->content),
                    $b instanceof DomainBlock\ImageBlock, $b instanceof DomainBlock\VideoBlock => $block->setMedia($media[$b->mediaId])->setCaption($b->caption),
                    $b instanceof DomainBlock\GalleryBlock => $block->setGalleryMediaIds($b->mediaIds()),
                    default => null,
                };
                $entry->addBlock($block);
            }
            $this->em->persist($entry);
        }
        $this->em->flush();

        $io->success(\sprintf(
            '%d Reisen, %d Themen, %d Schlagwörter, %d Medien und %d Einträge übernommen.',
            \count($trips), \count($topics), \count($keywords), \count($media), \count($this->json->entries()),
        ));

        return Command::SUCCESS;
    }

    private function hasContent(): bool
    {
        return $this->em->getRepository(Entity\Entry::class)->count([]) > 0
            || $this->em->getRepository(Entity\Media::class)->count([]) > 0;
    }

    private function purge(): void
    {
        foreach ([Entity\Block::class, Entity\Entry::class, Entity\Media::class, Entity\Trip::class, Entity\Topic::class, Entity\Keyword::class] as $class) {
            foreach ($this->em->getRepository($class)->findAll() as $entity) {
                $this->em->remove($entity);
            }
            $this->em->flush();
        }
    }
}
