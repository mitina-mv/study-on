<?php

namespace App\Tests;

class CoursePagesTest extends AbstractTest
{
    public function urlProviderSuccessful(): \Generator
    {
        yield ['/courses/'];
        yield ['/courses/new'];
        yield ['/courses/2'];
        yield ['/courses/2/edit'];
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
