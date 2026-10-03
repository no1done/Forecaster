<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class SecurityControllerTest extends WebTestCase
{
    private const string EMAIL = 'me@example.com';
    private const string PASSWORD = 'correct horse battery staple';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient(server: ['HTTP_ORIGIN' => 'http://localhost']);
        // Failed-login counts are cached on disk; start every test with none.
        self::getContainer()->get('cache.rate_limiter')->clear();

        $user = new User();
        $user->setEmail(self::EMAIL);
        $user->setPassword(self::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($user, self::PASSWORD));
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($user);
        $em->flush();
    }

    #[Test]
    #[DataProvider('protectedPages')]
    public function everyPageNeedsYouToLogIn(string $url): void
    {
        $this->client->request('GET', $url);

        self::assertResponseRedirects('/login');
    }

    /**
     * @return iterable<array{string}>
     */
    public static function protectedPages(): iterable
    {
        yield ['/'];
        yield ['/recurring'];
        yield ['/recurring/new'];
        yield ['/ad-hoc'];
        yield ['/ad-hoc/new'];
        yield ['/balances'];
    }

    #[Test]
    public function youCanLogInAndSeeTheForecast(): void
    {
        $this->client->request('GET', '/');
        $this->client->followRedirect();

        $this->logIn(self::PASSWORD);

        self::assertResponseRedirects('/');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Next 12 months');
    }

    #[Test]
    public function aWrongPasswordIsRejected(): void
    {
        $this->client->request('GET', '/login');
        $this->logIn('not my password');

        self::assertResponseRedirects('/login');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.error', 'Invalid credentials.');
        self::assertInputValueSame('_username', self::EMAIL);
    }

    #[Test]
    public function theLoginPageOffersToRememberYou(): void
    {
        $this->client->request('GET', '/login');
        $this->client->submitForm('Log in', [
            '_username' => self::EMAIL,
            '_password' => self::PASSWORD,
            '_remember_me' => true,
        ]);

        self::assertResponseRedirects('/');
        self::assertBrowserHasCookie('REMEMBERME');
    }

    #[Test]
    public function youCanLogOut(): void
    {
        $this->client->request('GET', '/login');
        $this->logIn(self::PASSWORD);
        $this->client->followRedirect();

        $this->client->submitForm('Log out');
        $this->client->request('GET', '/');

        self::assertResponseRedirects('/login');
    }

    #[Test]
    public function repeatedFailedLoginsAreThrottled(): void
    {
        $this->client->disableReboot();

        for ($i = 0; $i < 5; ++$i) {
            $this->client->request('GET', '/login');
            $this->logIn('guess '.$i);
        }
        $this->client->request('GET', '/login');
        $this->logIn(self::PASSWORD);

        self::assertResponseRedirects('/login');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.error', 'Too many failed login attempts');
    }

    private function logIn(string $password): void
    {
        $this->client->submitForm('Log in', [
            '_username' => self::EMAIL,
            '_password' => $password,
        ]);
    }
}
