# Задание 4 - Тестирование в проекте

## 0. Создание окружения для запуска тестов

Создание env.test и тестовой БД, применение миграций. Все получилось без проблем.


## 1. Разработка тестов
1. Готовый класс AbstractTest:

Проблемы:
- Класс ContainerAwareInterface вероятно устарел
    [issue](https://github.com/symfony/symfony-docs/issues/18440)

    В файле Undefined type, но почему-то работает, хотя класса в vendor не нашла, на гитхабе в текущей версии компонента тоже нет класса
    [symfony repository](https://github.com/symfony/symfony/tree/7.0/src/Symfony/Component/DependencyInjection)

- Метод getClient не переопределяется с указанной сигнатурой
    ```
    protected static function getClient($reinitialize = false, array $options = [], array $server = [])
    ```
    Ошибка: Method 'App\Tests\AbstractTest::getClient()' is not compatible with method 'Symfony\Bundle\FrameworkBundle\Test\WebTestCase::getClient()'.

    Был создан метод createTestClient, рабочий, но не согласованный.

2. Пишем тесты:

Проблемы: 
- Не обнуляются SEQUENCE в БД, фикструры загружаются каждый раз с новым ID.

    Для очистки БД была загружена зависимость `dama/doctrine-test-bundle`, но проблему не решило почему-то.

    TODO для правильной работы с ID в тестах были произведелны вручную настройки сиквансов в тестовой БД: ограничила максимум по количеству уроков/курсов соотв., включила зацикливание назначения Id. Но это плохо.

    Тут есть какой-то issue на тему,наверное, но я ничего не поняла
    [issue 2](https://github.com/doctrine/orm/issues/8893)

    Создала для обнуления сиквансов команду, но из-за нее перестали запускаться тесты с очень странной ошибкой ( Не найден файл фикса для БД FixPostgreSQLDefaultSchemaListener). Команда работала, но обнуляла в БД на проде :\ Пришлось удалить, но есть коммит. 

- В какой-то момент появилась ошибка
    ```
    App\Tests\CourseFunctionaltest::testHasLinkToDetailCourse
    LogicException: Booting the kernel before calling "Symfony\Bundle\FrameworkBundle\Test\WebTestCase::createClient()" is not supported, the kernel should only be booted once.
    ```

    Она возникает на любом методе, который запускается первым... Как починить не знаю, после чего появилась - тоже не знаю (

    UPD: сиквансы зло! Ошибка пропала, после того, как в классах проверки страниц были убраны запросы курса/урока.
- Иногда бывает ошибка в классах *PagesTest, если не находится элемент по id. Но при новом запуске все живет.

