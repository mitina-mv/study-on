<?php

namespace App\Tests;

use App\Command\ResetSequencesCommand;
use App\DataFixtures\CourseFixtures;
use App\Entity\Course;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\Response;

class CourseFunctionaltest extends AbstractTest
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
        $client = self::createTestClient();
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
    public function testCreateOkCourseForm(): void
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
        $client = self::createTestClient();
        $url = '/courses/1';

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
        $url = '/courses/1';

        $crawler = $client->request(
            'GET',
            "{$url}/edit"
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $form = $crawler->selectButton('Обновить')->form();
        $form['course[title]'] = 'Название курса';
        $form['course[description]'] = 'Описание курса';
        $form['course[code]'] = 'code2';

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertResponseRedirects($url);
    }

    /**
     * Проверка перехода на страницу урока с курса
     */
    public function testNavigateToLessonPage(): void
    {
        $client = self::createTestClient();
        $url = '/courses/1';

        $crawler = $client->request('GET', $url);

        $lessonLink = $crawler->filter('a.lesson-item')->first()->link();
        $crawler = $client->click($lessonLink);

        $this->assertResponseOk();
    }

    /**
     * Проверка заполнения формы создания нового курса (невалидные данные)
     */
    public function testCreateErrorCourseForm(): void
    {
        $client = self::createTestClient();
        $crawler = $client->request('GET', '/courses/new');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // неуникальный код
        $form = $crawler->selectButton('Сохранить')->form();
        $form['course[title]'] = 'Название курса';
        $form['course[description]'] = 'Описание курса';
        $form['course[code]'] = 'js';

        $client->submit($form);
        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            'li',
            'Символьный код плохой!'
        );

        // пустой код
        $form = $crawler->selectButton('Сохранить')->form();
        $form['course[title]'] = 'Название курса';
        $form['course[description]'] = 'Описание курса';
        $form['course[code]'] = '';

        $client->submit($form);
        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            'li',
            'Символьный код не может быть пустым'
        );

        // пустое название
        $form = $crawler->selectButton('Сохранить')->form();
        $form['course[title]'] = '';
        $form['course[description]'] = 'Описание курса';
        $form['course[code]'] = 'code1';

        $client->submit($form);
        $this->assertResponseCode(422);

        $this->assertSelectorTextContains(
            'li',
            'Название курса не может быть пустым'
        );
    }
}
