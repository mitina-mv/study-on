0. Доработки ЛР 2 по замечаниям:

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

    Инструкции после загрузки: 
    ```
     symfony/webpack-encore-bundle  instructions:
        * Install NPM and run npm install
        * Compile your assets: npm run dev
        * Or start the development server: npm run watch 
    ```

    Затем запускаем `docker compose run node yarn install` и `docker compose run node yarn add @symfony/webpack-encore --dev` - происходит какая-то магия и что-то success и Done. А значит все в порядке )

3. Настройка
    1. удалила `.enableBuildNotifications` в файле webpack.config.js