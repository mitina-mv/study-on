# Выполнение Задания 2

## 1. добавить в конфигурацию doctrine.yaml, секция dbal
```
# настройки для связки в postgresql
driver: 'pdo_pgsql'
server_version: '16.2'
url: '%env(resolve:DATABASE_URL)%'
```
Уточнить версию используемого postgresql:
```
docker exec study-on-postgres-1 psql -V
```
или 
```
docker compose exec postgres psql -V
```

## 2. Создать базу данных для studyOn:
```
docker compose exec php bin/console doctrine:database:create
```
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

## 3. создать слушателя для фикса создания схемы public:
фикс отсюда
https://gist.github.com/vudaltsov/ec01012d3fe27c9eed59aa7fd9089cf7#file-fixpostgresqldefaultschemalistener-php
FixPostgreSQLDefaultSchemaListener.php поместить в src/EventListener
перед названием класса добавить 
```
#[AsDoctrineListener(event: ToolEvents::postGenerateSchema, connection: 'default')]
```
В конец файла services.yaml добавить:
```
    App\Doctrine\EventListener\FixPostgreSQLDefaultSchemaListener:
        tags:
            - { name: doctrine.event_listener, event: postGenerateSchema }
```
Для создания миграции, которая не применится, можно изменить ее название на произвольное.

## 4. Создать миграции:
Для создания сущности можно добавить команду в local.mk:
```
entity:
	@${CONSOLE} make:entity
```
Для создания отношения урока к курсу тип поля нужно выбрать `relation`
Создаем и накатываем миграции:
```
make migration
make migrate
```

## 5. Создание фикстур:
Скачать зафисимость в контейнер с php:
```
docker compose exec php composer require doctrine/doctrine-fixtures-bundle
```
Команды для создания файла фикстур почему-то нет (?) :/

Команда для накатывания фикстур: `make fixtload`

## 6. Создание КРУД-контроллеров:
Магия с помощью команды `make:crud`, нужно делать внутри контейнера

Чтобы зайти внутрь контейнера, добавила команду:
```
exec php: 
	@$(COMPOSE) exec -it php bash
```
Чтобы выйди из контейнера, пиши `exit`

## 7. Изменение контроллеров, форм и страниц по заданию:

- *Для курсов базовый роутом делаем /courses, для уроков /lessons*

[x] Супер

Меняем перед именем класса контроллера: `#[Route('/courses')]`

Но еще есть файл routes.yaml и еще 2 способа.

- *При входе на главную происходит редирект на список курсов*

[x] Супер

В routes.yaml добавить:
```
homepage:
    path: /
    controller: Symfony\Bundle\FrameworkBundle\Controller\RedirectController::urlRedirectAction
    defaults:
        path: /courses
        permanent: true
```
Можно ли это изменить иначе, но без создания контроллера? Можно ли изменить имя страницы / c homepage на другое?

- *В списке курсов выводим название (ссылкой на страницу курса) и описание. В конце списка ссылку на добавления нового курса*

[x] Готово

- *На странице курса выводим название, описание, список уроков (ссылками на страницы уроков) в соответствии с заданным порядком и действия (вернуться к списку, редактировать, удалить, добавить урок). Каждый урок в списке представлен названием-ссылкой на страницу урока*

[x] Готово

- *На странице урока выводим название урока в заголовке, название курса (ссылкой на страницу курса), действия (редактировать, удалить) и контент*

[x] Готово

- *Доработать, чтобы после создания и удаления урока возвращал на страницу курса*

[x] Готово

В методы добавить вот такое:
```
return $this->redirectToRoute(
    'app_course_show', ['id' => $lesson->getCourse()->getId()],
    Response::HTTP_SEE_OTHER
);
```
- *Реализовать, чтобы создаваемый урок сразу привязывался к курсу, со страницы которого он создается (передавать в URL и на основе него добавлять HiddenType поле)*

[x] Работает, но есть TODO (техдолг)

По рекомендации передавать именно в формате ?course_id=ID, а не параметром в роуте, было сделано следующее (символ ~ |*тильда*| позволяет сделать конкатенацию строк в twig):
```
<a href="{{ path('app_lesson_new') ~ '?course_id=' ~ course.id }}">Добавить урок</a>
``` 
Получается, теперь роут /lessons/new может работать в двух режимах: добавление без начальной привязки к курс и с начальным указанием курса. Когда передан GET-параметр, тогда нужно запретить менять курс на какой-то другой и вывести его название в поле.

Сначала был добавлено необязательное поле в обработчик формы (тут это так называется?) LessonType, но его значения не было видно через `$options`. Поэтому пришлось забирать значение через Реквест `$courseId = $request->query->get('course_id');`, добавив в контруктор RequestStack.

Потом была попытка добавить DataTransformer, но она не увенчалась успехом. Поэтому был сделан костыль:
```
$course = $this->entityManager->getRepository(Course::class)->find($courseId);

if ($course) {
    $builder->add('course', EntityType::class, [
        'class' => Course::class,
        'choice_label' => 'title',
        'choice_value' => 'id',
        'data' => $course,
        'disabled' => true, // блокирую поле
    ]);
}
```
Однако после этого, при попытке сохранения данных формы, валидация не проходила. При `dd($form)` в контроллере увидела, что course = null.

Исправить получилось с помощью DataTransformer, но для этого пришлось передать в него еще и значение course_id из реквеста, так как по умолчанию его там нет (или я не знаю, как его найти). Но просто DataTransformer не все удалось починить, поэтому пришлось ввести зависимость от метода запроса:
```
if($request->getMethod() == 'POST')
{
    $builder->get('course')->addModelTransformer(new CourseToEntityTransformer($this->entityManager, $courseId));
}
```
Таким образом, оно работает и сохраняет, но требует исправления.
 
- *Добавить валидацию полей в формах в соответствии с ограничениями модели (в том числе проверку уникальности символьного кода курса)*

[x] Добавлено без фанатизма

При добавлении проверки уникальности через форму, происходит получение какой-то странной ошибки. Сейчас уникальность реализована через атрибут перед названием класса:
```
#[UniqueEntity(fields: ['code'], message: 'Символьный код плохой!')]
```