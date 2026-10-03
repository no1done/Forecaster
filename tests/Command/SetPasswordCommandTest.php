<?php

namespace App\Tests\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class SetPasswordCommandTest extends KernelTestCase
{
    private CommandTester $command;

    protected function setUp(): void
    {
        $this->command = new CommandTester((new Application(self::bootKernel()))->find('app:set-password'));
    }

    #[Test]
    public function itCreatesTheUser(): void
    {
        $this->command->setInputs(['a long enough password', 'a long enough password']);

        $this->command->execute(['email' => 'me@example.com']);

        $this->command->assertCommandIsSuccessful();
        $users = $this->users();
        self::assertCount(1, $users);
        self::assertSame('me@example.com', $users[0]->getUserIdentifier());
        self::assertTrue($this->hasher()->isPasswordValid($users[0], 'a long enough password'));
        self::assertStringContainsString('Created me@example.com', $this->command->getDisplay());
    }

    #[Test]
    public function itChangesAnExistingUsersPassword(): void
    {
        $this->command->setInputs(['a long enough password', 'a long enough password']);
        $this->command->execute(['email' => 'me@example.com']);

        $this->command->setInputs(['a different password', 'a different password']);
        $this->command->execute(['email' => 'me@example.com']);

        $this->command->assertCommandIsSuccessful();
        $users = $this->users();
        self::assertCount(1, $users);
        self::assertTrue($this->hasher()->isPasswordValid($users[0], 'a different password'));
        self::assertStringContainsString('Changed the password for me@example.com', $this->command->getDisplay());
    }

    #[Test]
    public function thePasswordsMustMatch(): void
    {
        $this->command->setInputs(['a long enough password', 'a typo password']);

        $status = $this->command->execute(['email' => 'me@example.com']);

        self::assertSame(Command::FAILURE, $status);
        self::assertStringContainsString('The passwords do not match.', $this->command->getDisplay());
        self::assertCount(0, $this->users());
    }

    #[Test]
    public function thePasswordMustBeAtLeastTwelveCharacters(): void
    {
        $this->command->setInputs(['too short', 'too short']);

        $status = $this->command->execute(['email' => 'me@example.com']);

        self::assertSame(Command::FAILURE, $status);
        self::assertStringContainsString('at least 12 characters', $this->command->getDisplay());
        self::assertCount(0, $this->users());
    }

    #[Test]
    public function theEmailMustBeValid(): void
    {
        $this->command->setInputs(['a long enough password', 'a long enough password']);

        $status = $this->command->execute(['email' => 'not-an-email']);

        self::assertSame(Command::FAILURE, $status);
        self::assertStringContainsString('valid email address', $this->command->getDisplay());
        self::assertCount(0, $this->users());
    }

    /**
     * @return list<User>
     */
    private function users(): array
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        return $em->getRepository(User::class)->findAll();
    }

    private function hasher(): UserPasswordHasherInterface
    {
        return self::getContainer()->get(UserPasswordHasherInterface::class);
    }
}
