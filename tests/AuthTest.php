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

        return self::getClient();
    }

    public function urlProviderSuccessful(): \Generator
    {
        yield ['/login'];
        yield ['/register'];
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
    }
}
