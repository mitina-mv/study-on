0. Настройки в биллинге
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

в метод регистрации контроллера src/Controller/AuthController.php добавляем создание refresh_token как описано в приложении к уроку. 
