<?php

declare(strict_types=1);

namespace App\Contact;

use App\Domain\Contact\ContactInbox;
use App\Domain\Contact\ContactMessage;
use Symfony\Component\Uid\Uuid;

/**
 * Speichert jede Nachricht als JSON-Datei. Der Ordner liegt in var/ und damit nie in Git.
 */
final readonly class FileContactInbox implements ContactInbox
{
    public function __construct(
        private string $directory,
    ) {
    }

    public function save(ContactMessage $message): string
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0o775, true) && !is_dir($this->directory)) {
            throw new \RuntimeException(\sprintf('Ordner "%s" kann nicht angelegt werden.', $this->directory));
        }

        $id = Uuid::v4()->toRfc4122();
        $json = json_encode([
            'id' => $id,
            'name' => $message->name,
            'email' => $message->email,
            'message' => $message->message,
            'createdAt' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DATE_ATOM),
        ], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);

        file_put_contents($this->directory.'/'.$id.'.json', $json."\n", \LOCK_EX);

        return $id;
    }
}
