<?php

namespace App\Tests;

use App\DataFixtures\CourseFixtures;
use App\Entity\Course;

class LessonPagesTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [CourseFixtures::class];
    }
    
    public function urlProviderSuccessful(): \Generator
    {
        yield ['/lessons/new/3'];
        yield ['/lessons/2'];
        yield ["/lessons/2/edit"];
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
        yield ['/lessons/'];
        yield ['/lessons/1000'];
        yield ['/lessons/1000/edit'];
    }

    /**
     * Тест на отсуствие доступа к закрытым / несущ. страницам
     * @dataProvider urlProviderNotFound
     */
    public function testPageNotFound($url): void
    {
        $client = self::createTestClient();
        $client->request('GET', $url);
        $this->assertResponseNotFound();
    }
}
