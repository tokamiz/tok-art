<?php

declare(strict_types=1);

namespace App\Contact;

use App\Domain\Contact\ContactInbox;
use App\Domain\Contact\ContactMessage;
use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Speichert Kontaktnachrichten in MariaDB; gelesen werden sie in der Verwaltung unter /admin.
 */
final readonly class DoctrineContactInbox implements ContactInbox
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function save(ContactMessage $message): string
    {
        $id = Uuid::v4()->toRfc4122();
        $this->em->persist(new Message(
            $id,
            $message->name,
            $message->email,
            $message->message,
            new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        ));
        $this->em->flush();

        return $id;
    }
}
