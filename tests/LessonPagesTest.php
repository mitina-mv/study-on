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
        $course = $this->getEntityManager()->getRepository(Course::class)->findAll()[0];
        $lesson = $course->getLessons()->first();
        
        yield ['/lessons/new/' . $course->getId()];
        yield ['/lessons/' . $lesson->getId()];
        yield ["/lessons/{$lesson->getId()}/edit"];
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
