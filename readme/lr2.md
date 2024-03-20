1. добавить в конфигурацию doctrine.yaml, секция dbal
```
# настройки для связки в postgresql
driver: 'pdo_pgsql'
server_version: '16.2'
url: '%env(resolve:DATABASE_URL)%'
```
Уточнить версию используемого postgresql:
`docker exec study-on-postgres-1 psql -V`
или 
`docker compose exec postgres psql -V`

2. Создать базу данных для studyOn:
`docker compose exec php bin/console doctrine:database:create`
Перейти в контейнер для проверки БД:
```
sudo -E docker compose exec -it postgres bash
psql -d <db_name> <user_name>
```
еще для проверки можно использовать валидатор. Для этого, например, можно добавить команду в local.mk:
```
schema validate:
	@${CONSOLE} doctrine:schema:validate
```
Подключить базу данных в pgAdmin:
1) создать группу серверов Интаро
2) создать сервер study_on (произвольное название), там указать:
    - General: name произвольно
    - Connection: 
        * host name\ address: localhost
        * port: 5432 
        * database: study_on
        * username: postgres
        * pass: (pass)
        * flag Save pass: true
    - SSH Tunnel: 
        * flag Use Ssh: true
        * host: (ip virtual machine)
        * port: 22
        * username: (имя пользователя машины)
        * pass: (пароль пользователя машины)
        * flag save pass: true
! Магия !

3. Создать миграции:
Для создания сущности можно добавить команду в local.mk:
```
entity:
	@${CONSOLE} make:entity
```
Создаем и накатываем миграции:
```
make migration
make migrate
```
