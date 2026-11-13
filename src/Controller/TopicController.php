<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\ContentSource;
use App\Domain\Entries;
use App\Domain\Filters;
use App\Domain\Lookup;
use App\Domain\Model\Topic;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/topics')]
final class TopicController extends AbstractController
{
    public function __construct(
        private readonly ContentSource $content,
    ) {
    }

    #[Route('', name: 'topic_index', methods: ['GET'])]
    public function index(): Response
    {
        $topics = $this->content->topics();
        $collator = new \Collator('de_DE');
        usort($topics, static fn (Topic $a, Topic $b): int => (int) $collator->compare($a->name, $b->name));

        return $this->render('topic/index.html.twig', [
            'topics' => $topics,
            'looseEntries' => Entries::sorted(Filters::apply($this->content->entries(), Filters::withoutTripAndTopic())),
        ]);
    }

    #[Route('/{id}', name: 'topic_show', methods: ['GET'])]
    public function show(string $id): Response
    {
        $topic = Lookup::byId($this->content->topics())[$id] ?? throw $this->createNotFoundException('Thema nicht gefunden');

        return $this->render('topic/show.html.twig', [
            'topic' => $topic,
            'entries' => Filters::apply($this->content->entries(), Filters::byTopic($id)),
        ]);
    }
}
