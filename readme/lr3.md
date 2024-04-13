0. Доработки ЛР 2 по замечаниям:
    1. Сортировка списка уроков на детальной странице курса

    Убрала сортирувку из twig-шаблона, в сущности Course прописала: 
    ```
    #[ORM\OrderBy(['serialNumber' => "asc"])]
      private Collection $lessons;
    ```
    2. Исправление формы создания урока: см файлы Lesson*.php

1. Установка node + yarn и Encore
    1. Установка node: в docker-compose.yml дописала:
    ```
    node:
        image: node:alpine
        environment:
        - YARN_CACHE_FOLDER=/yarn
        working_dir: /app
        user: ${UID:-1000}:${GID:-1000}
        volumes:
        - ${PWD}:/app
        - ${HOME}/.yarn:/yarn
    ```

    2. Проверить, что есть папка yarn в директории пользователя
    `ls ~ -al` и сменить владельца с root на пользователя `sudo chown mitina_mv ~/.yarn/`.

    3. Установка Encore через контейнер

    Команда `make require` по умолчанию не прописана в наших make-файлах, пришлось добавлять к команду:
    ```
    require:
	    @${COMPOSER} require $2
    ```
    Но она почему-то  не заработала, нет прав... :\

    Поэтому запускаю `docker compose exec php composer require encore`, устанавливается зависимость symfony/webpack-encore-bundle. 

    Инструкции после загрузки не актуальны, так как мы используем yarn. Что такое этот ваш yarn? Это менеджер пакетов, аналог npm. Если сделать `docker ps -a` можно увидеть, что наш контейнер с node не работает. Действительно, он был добавлен для загрузки зависимостей для фронта и запуска фронта. работающим все время, как например, БД, нода нам не нужна, а когда нужна, мы запускаем `docker compose run node` и пишем команду на выполнение в контейнере.

    Затем запускаем `docker compose run node yarn install` и `docker compose run node yarn add @symfony/webpack-encore --dev` - происходит какая-то магия и что-то success и Done. А значит все в порядке )

3. Настройка Encore и подключение Bootstrap
    1. удалила `.enableBuildNotifications` в файле webpack.config.js
    2. устанавливаем sass `docker compose run node yarn add sass-loader@^13.0.0 sass --dev`
    3. устанавливаем bootstrap и его зависимость `docker compose run node yarn add bootstrap @popperjs/core --dev` 
    4. в app.js добавляем `import './styles/app.scss';` сам файл стилей нужно переименовать с .css на .scss
    5. в app.scss добавляем `@import "~bootstrap/scss/bootstrap";`

      Подробнее в файлах: [app.js](/assets/app.js) и [app.scss](/assets/styles/app.scss)

4. Разработка страниц ошибки
    1. Действовать по алгоритму здесь https://symfony.com/doc/current/controller/error_pages.html#overriding-the-default-error-templates, п.1
    2. Страницы ошибок можно просматривать без перехода в прод-режим на страницах `/_error/{statusCode}`