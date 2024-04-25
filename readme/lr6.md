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
