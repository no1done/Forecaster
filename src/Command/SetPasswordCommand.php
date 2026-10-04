<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(name: 'app:set-password', description: 'Create a user, or change their password')]
final class SetPasswordCommand
{
    private const int MIN_LENGTH = 12;

    public function __construct(
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(SymfonyStyle $io, #[Argument('The email address you log in with')] string $email): int
    {
        if (\count($this->validator->validate($email, new Assert\Email())) > 0) {
            $io->error(\sprintf('"%s" is not a valid email address.', $email));

            return Command::FAILURE;
        }

        $password = (string) $io->askHidden('New password');
        if (mb_strlen($password) < self::MIN_LENGTH) {
            $io->error(\sprintf('The password must be at least %d characters.', self::MIN_LENGTH));

            return Command::FAILURE;
        }
        if ($password !== $io->askHidden('Repeat the password')) {
            $io->error('The passwords do not match.');

            return Command::FAILURE;
        }

        $user = $this->users->findOneBy(['email' => $email]);
        $isNew = null === $user;
        if ($isNew) {
            $user = new User();
            $user->setEmail($email);
            $this->em->persist($user);
        }
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $this->em->flush();

        $io->success($isNew ? \sprintf('Created %s.', $email) : \sprintf('Changed the password for %s.', $email));

        return Command::SUCCESS;
    }
}
