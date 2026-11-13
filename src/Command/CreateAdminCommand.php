<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Legt ein Konto für die Verwaltung an oder setzt sein Passwort neu.
 *
 *   php bin/console app:admin:create name@example.org
 *   php bin/console app:admin:create name@example.org --sql   → nur SQL ausgeben (für phpMyAdmin bei STRATO)
 */
#[AsCommand(name: 'app:admin:create', description: 'Konto für die Verwaltung anlegen oder Passwort neu setzen')]
final class CreateAdminCommand extends Command
{
    public const MIN_PASSWORD_LENGTH = 12;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'E-Mail-Adresse zum Anmelden')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Passwort (sonst wird es verdeckt abgefragt)')
            ->addOption('sql', null, InputOption::VALUE_NONE, 'Nicht speichern, sondern SQL ausgeben (z. B. für phpMyAdmin)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = mb_strtolower(trim((string) $input->getArgument('email')));
        if (false === filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            $io->error('Bitte eine gültige E-Mail-Adresse angeben.');

            return Command::INVALID;
        }

        $password = $input->getOption('password');
        if (!\is_string($password)) {
            $question = (new Question('Passwort'))->setHidden(true)->setHiddenFallback(false);
            $password = (string) $io->askQuestion($question);
        }
        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            $io->error(\sprintf('Das Passwort muss mindestens %d Zeichen lang sein.', self::MIN_PASSWORD_LENGTH));

            return Command::INVALID;
        }

        $user = $this->em->getRepository(AdminUser::class)->findOneBy(['email' => $email]) ?? new AdminUser($email);
        $user->setPassword($this->hasher->hashPassword($user, $password));

        if ($input->getOption('sql')) {
            $output->writeln(\sprintf(
                "INSERT INTO admin_user (email, password, roles) VALUES ('%s', '%s', '[\"ROLE_ADMIN\"]')\n"
                ."  ON DUPLICATE KEY UPDATE password = VALUES(password);",
                addslashes($email),
                addslashes($user->getPassword()),
            ));

            return Command::SUCCESS;
        }

        $this->em->persist($user);
        $this->em->flush();
        $io->success(\sprintf('Konto %s ist bereit. Anmelden unter /login.', $email));

        return Command::SUCCESS;
    }
}
