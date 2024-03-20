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

