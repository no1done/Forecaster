<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

abstract class BrowserTestCase extends WebTestCase
{
    use ClockSensitiveTrait;

    protected KernelBrowser $client;

    private int $userId;

    protected function setUp(): void
    {
        // Same-origin header, as a browser sends, for stateless CSRF protection.
        $this->client = self::createClient(server: ['HTTP_ORIGIN' => 'http://localhost']);
        self::mockTime('2026-10-01 09:00');

        $user = $this->createUser('me@example.com');
        $this->userId = $user->getId();
        $this->client->loginUser($user);
    }

    /**
     * The logged-in user, attached to the current entity manager.
     */
    protected function user(): User
    {
        return $this->em()->getReference(User::class, $this->userId);
    }

    protected function createUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('not used: tests log in directly');
        $this->persist($user);

        return $user;
    }

    protected function em(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }

    protected function persist(object ...$entities): void
    {
        foreach ($entities as $entity) {
            $this->em()->persist($entity);
        }
        $this->em()->flush();
        $this->em()->clear();
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return list<T>
     */
    protected function all(string $class): array
    {
        $this->em()->clear();

        return $this->em()->getRepository($class)->findAll();
    }

    protected static function date(string $date): \DateTimeImmutable
    {
        return new \DateTimeImmutable($date);
    }
}
