<?php

namespace App\Tests;

use App\DataFixtures\CourseFixtures;
use App\Entity\Course;
use Symfony\Component\HttpFoundation\Response;

class LessonFunctionaltest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [CourseFixtures::class];
    }

    /**
     * Проверка состава страницы детальной страницы урока
     */
    public function testStructureLessonPage()
    {
        $client = static::createTestClient();

        $course = $this->getEntityManager()->getRepository(Course::class)->findAll()[0];
        $lesson = $course->getLessons()->first();

        $url = "/lessons/{$lesson->getId()}";
        $client->request('GET', $url);

        // страница доступна
        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // есть заголовок, контент, кнопки
        $this->assertSelectorExists('h1', $lesson->getSerialNumber() .'. '. $lesson->getName());
        $this->assertSelectorExists('div.lesson-content');
        $this->assertSelectorExists('a.btn-dark', 'Назад к курсу');
        $this->assertSelectorExists('a.btn-secondary', 'Редактировать');
        $this->assertSelectorExists('button.btn-danger', 'Удалить');
    }

    /**
     * Создание нового урока для курса
     */
    public function testCreateLessonForm()
    {
        $client = static::createTestClient();

        $course = $this->getEntityManager()->getRepository(Course::class)->findAll()[0];
        $url = "/lessons/new/{$course->getId()}";
        
        $crawler = $client->request('GET', $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $form = $crawler->selectButton('Сохранить')->form();
        $form['lesson[name]'] = 'Название урока';
        $form['lesson[content]'] = 'Описание курса';
        $form['lesson[serialNumber]'] = 2;

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());

        $crawler = $client->followRedirect();

        $this->assertCount(
            count($course->getLessons()),
            $crawler->filter('a.lesson-item')
        );
    }

    /**
     * Создание нового урока (невалидные данные)
     */
    public function testFailCreateLessonForm()
    {
        $client = static::createTestClient();

        $course = $this->getEntityManager()->getRepository(Course::class)->findAll()[0];
        $url = "/lessons/new/{$course->getId()}";
        
        $crawler = $client->request('GET', $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $form = $crawler->selectButton('Сохранить')->form();
        $form['lesson[name]'] = '';
        $form['lesson[content]'] = 'Описание курса';
        $form['lesson[serialNumber]'] = 2;

        $client->submit($form);

        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            'li',
            'Название урока не может быть пустым'
        );

        $form = $crawler->selectButton('Сохранить')->form();
        $form['lesson[name]'] = 'Название';
        $form['lesson[content]'] = 'Описание курса';
        $form['lesson[serialNumber]'] = null;

        $client->submit($form);

        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            'li',
            'Номер урока не может быть пустым'
        );

        $form = $crawler->selectButton('Сохранить')->form();
        $form['lesson[name]'] = 'Название';
        $form['lesson[content]'] = 'Описание курса';
        $form['lesson[serialNumber]'] = 100500;

        $client->submit($form);

        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            'li',
            'Больше 1 000 и меньше 1 нельзя :('
        );
    }
    /**
     * Редактирование урока
     */
    public function testEditLessonForm()
    {
        $client = static::createTestClient();

        $course = $this->getEntityManager()->getRepository(Course::class)->findAll()[0];
        $lesson = $course->getLessons()->first();
        $url = "/lessons/{$lesson->getId()}";
        
        $crawler = $client->request('GET', "$url/edit");

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $form = $crawler->selectButton('Обновить')->form();
        $form['lesson[name]'] = 'Название урока';
        $form['lesson[content]'] = 'Описание курса';
        $form['lesson[serialNumber]'] = 3;

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertResponseRedirects("/courses/{$course->getId()}");
    }
    /**
     * Удаление урока
     */
    public function testDeleteLesson()
    {
        $client = static::createTestClient();

        $course = $this->getEntityManager()->getRepository(Course::class)->findAll()[0];
        $lesson = $course->getLessons()->first();
        $countLessons = count($course->getLessons());

        $url = "/lessons/{$lesson->getId()}";
        $crawler = $client->request('GET', $url);

        $form = $crawler->selectButton('Удалить')->form();
        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertResponseRedirects("/courses/{$course->getId()}");

        $crawler = $client->followRedirect();

        $this->assertCount(
            $countLessons - 1,
            $crawler->filter('a.lesson-item')
        );
    }
}
