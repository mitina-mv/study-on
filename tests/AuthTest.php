<?php

namespace App\Tests;

use App\Command\ResetSequencesCommand;
use App\DataFixtures\CourseFixtures;
use App\Entity\Course;
use App\Service\BillingClient;
use App\Tests\Mock\BillingClientMock;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class AuthTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        // обнуление сиквансов перед загрузкой фикстур
        $command_reset_seq = new ResetSequencesCommand($this->getEntityManager()->getConnection());
        $input = new ArrayInput([]);
        $output = new NullOutput();
        $command_reset_seq->run($input, $output);

        return [CourseFixtures::class];
    }

    private function localClient()
    {
        self::createTestClient()->disableReboot();

        self::createTestClient()->getContainer()->set(
            BillingClient::class,
            new BillingClientMock()
        );

        return self::createTestClient();
    }

    public function urlProviderSuccessful(): \Generator
    {
        yield ['/login'];
        yield ['/registration'];
    }
    /**
     * Тест на доступность страниц без авторизации
     * @dataProvider urlProviderSuccessful
     */
    public function testPageSuccessful($url): void
    {
        $client = $this->localClient();
        $client->request('GET', $url);
        $this->assertResponseOk();
    }

    public function testLoginSuccess(): void
    {
        $client = $this->localClient();
        $crawler = $client->request('GET', '/login');
        
        $form = $crawler->selectButton('Войти')->form(
            [
                'email' => 'user@email.example',
                'password' => 'user@email.example'
            ]
        );

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
        self::assertEquals('/courses/', $client->getRequest()->getPathInfo());
    }

    public function testLoginFail(): void
    {
        $client = $this->localClient();
        $crawler = $client->request('GET', '/login');
        
        $form = $crawler->selectButton('Войти')->form(
            [
                'email' => 'user@email.example',
                'password' => '123123'
            ]
        );

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());
        $crawler = $client->followRedirect();
        self::assertEquals('/login', $client->getRequest()->getPathInfo());
        $this->assertCount(1, $crawler->filter('.alert'));
    }

    

    public function testRegisterSuccess(): void
    {
        $client = $this->localClient();
        $crawler = $client->request('GET', '/registration');
        
        $form = $crawler->selectButton('Зарегистрироваться')->form(
            [
                'user_registration[email]' => 'user1@email.example',
                'user_registration[password][first]' => 'user1@email.example',
                'user_registration[password][second]' => 'user1@email.example',
            ]
        );

        $client->submit($form);

        // TODO
        // $this->assertTrue($client->getResponse()->isRedirect());
        // $client->followRedirect();
        // self::assertEquals('/courses/', $client->getRequest()->getPathInfo());
    }
}
