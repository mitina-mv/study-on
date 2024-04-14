# Задание 4 - Тестирование в проекте

## 0. Создание окружения для запуска тестов

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

