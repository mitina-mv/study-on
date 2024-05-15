# Задание 6 - Авторизация и регистрация пользователя в StudyOn

## 0. Изменение docker-compose.yaml

Создаем новую сеть: `docker network create billing_network`

Для обоих сервисов прописываем:
```
networks:
  default:
    external: true
    name: billing_network
```
По документации сказано только в study-on, но так не работает. 

## 1. Авторизация в StudyOn

Добавляем адрес биллинга в .env и в файле Billing Client получаем значение через $_ENV\['BILLING_SERVER'\].

Ошибки тоже добавляем (в Exception), суть - создать просто новый класс ошибки, чтобы обрабатывать разные случаи.

Формулировка "В методе  authenticate разместите запрос к методу авторизации." означает что:
- в файле src/Security/BillingAuthenticator.php в методе authenticate 
- нужно сделать запрос к методу authenticate из файла src/Service/BillingClient.php
- Еще нужен BillingAuthenticatorController для того, чтобы была страница с формой входа. Вот тут речь не про нее )

Настройка доступа к страницам делается через аннотацию IsGranted. Пример:  #\[IsGranted('ROLE_SUPER_ADMIN')\]

Чтобы ошибки выводились в форме, нужно выбрасывать исключение класса CustomUserMessageAuthenticationException (с этим внимательнее)
```
throw new CustomUserMessageAuthenticationException(
  'Произошла ошибка во время авторизации: ' . $e->getMessage()
);
```

## 2. Регистрация в study-on

Для реализации регистрации создаем форму src/Form/UserRegistrationType.php и метод в контроллере src/Controller/BillingAuthenticatorController.php. Ну и страницы конечно )

При реализации метода регистрации оказалось, что авторизация проходит как-то странно без доп запроса пользователя:
```
$user = new User();
$user->setApiToken($response['token']);

$userResponse = $billingClient->getCurrentUser($response['token']);

$user->setRoles($userResponse['roles']);
$user->setBalance($userResponse['balance']);
$user->setEmail($userResponse['username']);
```
Причины не выяснены. Но если без этого, то от пользователя будет сохраннено в сессии только apiToken.

Чтобы форма выводила ошибки в блоке с ошибками, пришлось сделать:
```
foreach ($form->getErrors(true, false) as $error) {
    foreach ($error as $formError) {
        foreach ($formError as $e) {
            $errors[] = $e->getMessage();
        }
    }
}
```

## 3. Тестирование авторизации и регистрации

Теперь, когда некоторые страницы могут быть недоступные пользователям с определенной роли или не авторизованным, нужно не только написать тесты на новый функционал, но и изменить страные тесты. 

  1. Сначала напишем тесты на новый функционал

    - создаем tests/Mock/BillingClientMock.php - этот класс будет имитировать поведение нашего реального биллинг-сервиса. Нужен, чтобы не нагружать наш сервис и не портить продовскую БД в ходе проведения тестов (так как сервис не знает, что этот запрос - тестирование, у него не включается тестовая среда, когда мы запускаем наши тесты из StudyOn)
    - создаем файлик с тестами, у меня это tests/AuthTest.php.
    - делаем подмену сервиса на мок. Пример:
      ```
      private function localClient()
      {
          $client = self::createTestClient();

          $client->disableReboot();

          $client->getContainer()->set(
              BillingClient::class,
              new BillingClientMock('')
          );

          return $client;
      }
      ```
      ! Важно. Это почему-то не работает ). Выяснилось на методе регистрации, но получиться и без регистрации. Просто выключите сервис биллинга и попробуйте провести тест авторизации - получите ошибку, что сервис недоступен (а в моке мы такое поведение не описывали). Пока не исправлено.
    - команда для запуска одного файла теста `docker compose exec php bin/phpunit tests/AuthTest.php`. Я еще добавила в make вот такое, но не сложилось:
      ```
      test: 
	      @${PHP} bin/phpunit $2
      ```
      Возможно, проблемы с правами из-под make

  2. Переписываем старый функционал
    - Для того, чтобы не писать в каждом файле метод авторизации, реалиуем его в хелпере. Например: tests/Helpers/AuthHelper.php

    Как реализуем: через трейт. Трейт (Trait) - это механизм, который позволяет повторно использовать методы в различных классах. Это вариант линейного наследования, а не иерархии, как в привычном ООП. Здесь мы можем иметь разные классы, но дать им общую логику, расширив набор их методов и свойсв неким "включаемым модулем". 
    
    Там, где нужны методы авторизации (а у меня они нужны не везде), добавляем `use AuthHelper;`. 

    - Все ранее написанные методы тестировали функционал добавления/редактирования/удаления. Это все может делать админ, поэтому в методы добавляем `$client = $this->createAuthorizedClient($this->adminEmail, $this->adminEmail);`

    - Для проверки, что юзер не может делать то, что может делать админ, добавляем новые методы. Проверяем там доступность страницы и возможность прямого запроса. В тесте на уроки (tests/LessonFunctionalTest.php) ко мне пришло озарение и было сделано так: 
    ```
    public function urlProviderUserRequests(): \Generator
    {
        
        yield ['/lessons/new/3', Response::HTTP_FORBIDDEN, true];
        yield ['/lessons/2', Response::HTTP_OK];
        yield ["/lessons/2/edit", Response::HTTP_FORBIDDEN, true];
    }
    /**
     * Тест на проверку доступности страниц
     * и возможности отправки прямых запросов на созд/ред
     * @dataProvider urlProviderUserRequests
     * Role: User
     */
    public function testUserRequests($url, $code, $includeFormData = false): void
    {
        $client = $this->createAuthorizedClient($this->userEmail, $this->userEmail);

        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame($code);

        if ($includeFormData) {
            $formData = [
                'lesson' => [
                    'name' => 'Название',
                    'content' => 'Контент',
                    'serialNumber' => 3
                ]
            ];
    
            $client->request('POST', $url, $formData);
        } else { // это запрос на удаление урока
            $client->request('POST', $url);
        }
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
    ```