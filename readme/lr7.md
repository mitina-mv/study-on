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

!UPD потом оказалось, что запросы с авторизацией не работаеют. Получилось исправить как описано в документации Интаро, но вероятно важно разместить штуку с рефрешем перед объявлением api:
```
...
api_token_refresh:
    pattern: ^/api/v1/token/refresh
    stateless: true
    refresh_jwt:
        check_path: /api/v1/token/refresh

api:
    pattern: ^/api
    stateless: true
    jwt: ~
...
```

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
мы делаем из курса массив, чтобы потом без проблем заменить его поле тип на то, что нужно нам )


! Важно. Была добавлена сикурность на роут, но она не работает почему-то (и работает не она, пришлось писать проверку что юзер не пустой)
- { path: ^/api/v1/courses/\w+/pay, roles: IS_AUTHENTICATED_FULLY }
## 5. Фиксация изменения баланса
! Важно. Метод пополнения баланса (в API и с StudyOn) не реализуется, только в сервисе. Почему-то. 

## 6. Оплата курсов
Не забудь обновить баланс твоего пользователя
```
// обновляем баланс
$userResponse = $this->billingClient->getCurrentUser($user->getApiToken());
$user->setBalance($userResponse['balance']);
```

! Baжно. ОЧЕНЬ. У меня не согласованность биллинга и мока, один возвращает error[\'type\'], а мок возвращает message
Надо это исправить когда-нибудь )

## 7. Фикстуры и тесты
Фикстуры нужно добавить в биллинг-систему. Важно не забыть накатить миграции, а то не будет работать ))

Я туда добавила обнуление сиквансов (да-да, снова о них)
```
// обнуление сиквансов
$sequences = ['course_id_seq', 'transaction_id_seq'];

foreach ($sequences as $sequence) {
    $sql = sprintf("SELECT setval('%s', 1, false);", $sequence);
    $this->connection->executeQuery($sql);
}
```
И еще из интересного - чтобы транзакции в фикстурах не протухли, был сделан финт ушами с датами:
```
[
    "create_at" => date('Y-m-dTH:i:s', time() - 2 * 24 * 60 * 60),
    "type" => "payment",
    "course_code" => "php",
    "amount" => 2500,
    'expires_at' => null,
],
```

8. Заметки

Если постоянно ломается гит c ошибкой 'object file ... is empty'
```
find .git/objects/ -type f -empty | xargs rm
git fetch -p
git fsck --full
```

Если копится много контейнеров node (у меня почему-то при каждом билде создается новый контейнер, и в итоге их дофига - штук 5 минимум)
```
docker stop $(docker ps -aq)
docker remove $(docker ps -aq)
```

Если нужно запустить один тест:
```
docker compose exec php bin/phpunit tests/CourseTest.php
```