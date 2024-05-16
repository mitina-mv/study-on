## 0. Настройки в биллинге
Загружаем зависимость и генерируем миграции. У нас есть команды `make migration` и `make migrate` для этого )

Метод refresh token создаем в контроллере src/Controller/AuthController.php, в реализации он не нуждается:
```
#[Route('/token/refresh', name: 'api_refresh', methods: ['POST'])]
public function refresh(): void
{
}
```


в config/packages/security.yaml добавить:
```
security:
    ...
    firewalls:
        ...
        api_token_refresh:
            pattern: ^/api/v1/token/refresh
            stateless: true
            refresh_jwt:
                check_path: /api/v1/token/refresh
    ...
    access_control:
        - { path: ^/api/v1/token/refresh, roles: PUBLIC_ACCESS }
```
! Важно. После этого был сделан запрос на refresh_token. Оказалось, что он требует json вида:
```
{
    "refresh_token": "e568..."
}
```
Была получена ошибка: The controller must return a \"Symfony\\Component\\HttpFoundation\\Response\" object but it returned null. Did you forget to add a return statement somewhere in your controller?

По документации https://github.com/markitosgv/JWTRefreshTokenBundle?tab=readme-ov-file#configure-the-authenticator вижу, что разработчики предлагают добавить в api вот такое:
```
# config/packages/security.yaml
security:
    firewalls:
        api:
            ...
            jwt: ~
            refresh_jwt:
                check_path: /api/token/refresh
```
Было добавлено подобное по описанию, но возникла другая ошибка:
```
Because you have multiple authenticators in firewall "api", you need to set the "entry_point" key to one of your authenticators ("jwt", "refresh_jwt") or a service ID implementing "Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface". The "entry_point" determines what should happen (e.g. redirect to "/login") when an anonymous user tries to access a protected page.
```
Все заработало в моем случае вот так:
```
# config/packages/security.yaml
security:
    firewalls:
        api:
            pattern: ^/api
            stateless: true
            # jwt: ~
            refresh_jwt:
                check_path: /api/v1/token/refresh
```
И так запрос отработал и токен пришел новый. Продолжаем...

в метод регистрации контроллера src/Controller/AuthController.php добавляем создание refresh_token как описано в приложении к уроку. 

## 1. Настройки в StudyOn

- Добавляем поле `private $refreshToken` в наш src/Security/User.php и методы получения-установки (геттеры-сеттеры 😎)
- Добавляем наш сервис по декодингу токена src/Service/JwtDecoder.php
- В биллинг-сервис добавляем метод refresh, чтобы делать запрос
- Реализуем UserProvider::refreshUser. Нужно декодировать apiToken юзера, и из него получить время истекания (?).

Метод сделан, а как проверить пока непонятно )

## 2. Модели данных в сервисе StudyOn.Billing
С помощью команды `make entity` добавляем сущности Course и Transaction в составе, описанном в документации.

Обратите внимание на поле "Срок действия до (дата и время, обязательно для списания по арендуемым курсам)
". То есть оно `#[ORM\Column(nullable: true)]`

## 3. Методы получения данных из StudyOn.Billing
1. Метод получения курсов
- создаем контроллер CourseController с конструктором 
```
public function __construct(
        private CourseRepository $courseRepository,
    ) {
    }
```
`$courseRepository` нужен для доступа к репозиторию курсов (чтобы во все методы не передавать его)
- наша сикурность тоже требует коррекции:
```
access_control:
    - { path: ^/api/v1/courses, roles: PUBLIC_ACCESS }
```
- метод получения имени типа по его цифровому значению. Как так, да вот так... )
```
public function getTypeName(): string
{
    switch ($this->type) {
        case 1:
            return 'free';
        case 2:
            return 'rent';
        case 3:
            return 'buy';
        default:
            return 'unknown';
    }
}
```
- метод получения курсов:
```
#[Route('/courses', name: 'api_courses', methods: ['GET'])]
    public function index(): JsonResponse
    {}
```
тут делаем вот такое:
```
foreach ($courses as $course) {
    $item = $course->toArray(); // самописный метод
    $item['type'] = $course->getTypeName();

    $result[] = $item;
}
```
мы делаем из курса массив, чтобы потом без проблем заменить его поле тип на то, что нужно нам. вот и ружье выстрелило )

## 5. Фиксация изменения баланса


## 6. Оплата курсов

## 6. Фикстуры и тесты




