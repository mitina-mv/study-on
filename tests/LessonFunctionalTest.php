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

    /**
     * Создание нового урока (невалидные данные)
     */

    /**
     * Редактирование урока
     */

    /**
     * Удаление урока
     */

    /**
     * Путь клиента: проверка переходов в рамках одного курса
     */
}
