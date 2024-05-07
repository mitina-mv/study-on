<?php

namespace App\Tests;

use App\Command\ResetSequencesCommand;
use App\DataFixtures\CourseFixtures;
use App\Entity\Course;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class CoursePagesTest extends AbstractTest
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
    
    public function urlProviderSuccessful(): \Generator
    {
        yield ['/courses/'];
    }
    /**
     * Тест на доступность страниц
     * @dataProvider urlProviderSuccessful
     */
    public function testPageSuccessful($url): void
    {
        $client = static::createTestClient();
        $client->request('GET', $url);
        $this->assertResponseOk();
    }
    
    public function urlProviderRedirectToLogin(): \Generator
    {
        yield ['/courses/new'];
        yield ["/courses/2"];
        yield ["/courses/2/edit"];
    }
    /**
     * Тест на доступность страниц
     * @dataProvider urlProviderSuccessful
     */
    public function testPageRedirectToLogin($url): void
    {
        $client = static::createTestClient();
        $client->request('GET', $url);
        $this->assertTrue($client->getResponse()->isRedirect('/login'));
    }

    public function urlProviderNotFound(): \Generator
    {
        yield ['/123/'];
        yield ['/courses/1000'];
        yield ['/courses/1000/edit'];
    }

    /**
     * Тест на отсуствие доступа к закрытым / несущ. страницам
     * @dataProvider urlProviderNotFound
     */
    public function testPageNotFound($url): void
    {
        $client = self::getClient();
        $client->request('GET', $url);
        $this->assertResponseNotFound();
    }

    /**
     * Осуществление переадрессации с главной страницы / на /courses
     */
    public function testRedirectToCoursesPage(): void
    {
        $client = self::createTestClient();
        $client->request('GET', '/');

        $this->assertResponseRedirects('/courses', 301);
    }
}
