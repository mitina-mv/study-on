<?php

namespace App\Tests;

class CourseTest extends AbstractTest
{
    /**
     * Осуществление переадрессации с главной страницы / на /courses
     */
    public function testRedirectToCoursesPage(): void
    {
        $client = self::createTestClient();
        $client->request('GET', '/');

        $this->assertResponseRedirects('/courses', 301);
    }
    /**
     * Страница с курсами открывается
     */
    public function testCoursesPageIsSuccessful(): void
    {
        $client = self::createTestClient();
        $client->request('GET', '/courses/');

        $this->assertResponseIsSuccessful();
    }

    /**
     * Проверка наличия ссылки для перехода на детальную страницу курса
     */
    public function testHasLinkToDetailCourse(): void
    {
        $client = self::createTestClient();
        $url = '/courses/';

        $crawler = $client->request('GET', $url);
        $link = $crawler->selectLink('Подробнее');
        dd($link);

        $crawler = $client->click($link);

        $this->assertResponseOk();
    }

    /**
     * Показ страницы 404 на несуществующий курс
     */
    public function testFailedDetailCoursePage(): void
    {
        $client = self::createTestClient();
        $url = '/courses/1000';

        $client->request('GET', $url);
        $response = $client->getResponse();

        $this->assertEquals(404, $response->getStatusCode());
    }
    
    /**
     * Проверка детальной страницы курса и элементов на ней
     */
    /* public function testOkDetailCoursePage(): void
    {
        $client = AbstractTest::createTestClient();
        $url = '/courses/1';

        $crawler = $client->request('GET', $url);

        // страница доступна
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        // есть заголовок, список занятий
        $this->assertSelectorExists('<h1>');
        $this->assertSelectorExists('<ul class="list-group">');
    } */
}
