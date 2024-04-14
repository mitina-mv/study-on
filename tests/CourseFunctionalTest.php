<?php

namespace App\Tests;

use Symfony\Component\HttpFoundation\Response;

class CourseFunctionaltest extends AbstractTest
{
    /**
     * Проверка наличия ссылки для перехода на детальную страницу курса
     */
    public function testHasLinkToDetailCourse(): void
    {
        $client = self::createTestClient();
        $url = '/courses/';

        $crawler = $client->request('GET', $url);
        $link = $crawler->selectLink('Подробнее')->link();

        $crawler = $client->click($link);

        $this->assertResponseOk();
    }
    
    /**
     * Проверка детальной страницы курса и элементов на ней
     */
    public function testOkDetailCoursePage(): void
    {
        $client = AbstractTest::createTestClient();
        $url = '/courses/1';

        $client->request('GET', $url);

        // страница доступна
        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // есть заголовок, список занятий, кнопки
        $this->assertSelectorExists('h1');
        $this->assertSelectorExists('ul.list-group');
        $this->assertSelectorExists('[role=group]');
        $this->assertSelectorExists('a.btn-dark', 'К списку курсов');
        $this->assertSelectorExists('a.btn-secondary', 'Редактировать');
        $this->assertSelectorExists('a.btn-success', 'Добавить урок');
    }

    /**
     * Проверка заполнения формы создания нового курса
     */
    public function testCreateCourseForm(): void
    {
        $client = self::createTestClient();
        $crawler = $client->request('GET', '/courses/new');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $form = $crawler->selectButton('Сохранить')->form();
        $form['course[title]'] = 'Название курса';
        $form['course[description]'] = 'Описание курса';
        $form['course[code]'] = 'code1';

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());

        $crawler = $client->followRedirect();

        $this->assertCount(4, $crawler->filter('.card'));
    }

    /**
     * Проверка удаления курса
     */
    public function testDeleteCourse(): void
    {
        $client = AbstractTest::createTestClient();
        $url = '/courses/3';

        $crawler = $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $form = $crawler->selectButton('Удалить')->form();
        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());

        $crawler = $client->followRedirect();

        $this->assertCount(2, $crawler->filter('.card'));
    }

    /**
     * Проверка редактирования курса
     */
    public function testEditCourseForm(): void
    {
        $client = self::createTestClient();
        $crawler = $client->request('GET', '/courses/2/edit');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $form = $crawler->selectButton('Обновить')->form();
        $form['course[title]'] = 'Название курса';
        $form['course[description]'] = 'Описание курса';
        $form['course[code]'] = 'code2';

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertResponseRedirects('/courses/2');
    }

    /**
     * Проверка перехода на страницу урока с курса
     */
    public function testNavigateToLessonPage(): void
    {
        $client = self::createTestClient();
        $crawler = $client->request('GET', '/courses/2');

        $lessonLink = $crawler->filter('a.lesson-item')->first()->link();
        $crawler = $client->click($lessonLink);

        $this->assertResponseOk();
    }
}
