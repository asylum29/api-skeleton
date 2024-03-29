# Symfony api-skeleton
Шаблон API для Symfony.
## Особенности
- автоматический парсинг входящего http-запроса в DTO-объект в параметре действия контроллера
- контроллер *BaseController* с методами *success()* и *error()* с поддержкой сериализации и методом *validate()* для валидации
- сквозная обработка ошибок для http-запросов
- генерация документации с использованием [NelmioApiDocBundle](https://symfony.com/bundles/NelmioApiDocBundle/current/index.html)
- префикс *api* для всех роут
## Создание нового проекта
```
composer create-project asylum29/api-skeleton <project_name>
```
Создайте *.env.local* на базе *.env* и укажите требуемые параметры.
## Использование
### Сериализация
```php
// унаследуйте контроллер от BaseController
class CustomController extends BaseController
{
    /**
     * @Route("/custom", name="app_custom")
     */
    public function index(): Response
    {
        ...
        return $this->success($dataOrEntity);
    }
}
```
### Группы сериализации
```php
// в Entity или DTO
/** @Groups("groupName") */
private $field;
/** @Groups("groupName") */
public function getField(): fieldtype

// в контроллере
return $this->success($dataOrEntity, ['groups' => 'groupName']);
```
### Постраничная навигация
```php
// в контроллере
return $this->success(
    $entities,
    [
        'current_page' => $page,
        'per_page' => $perPage,
        'total' => $count,
    ]
);
```
### Генерация ошибки
```php
// в контроллере
public function index(): Response
{
    ...
    if (!$valid) {
        $this->error($message, $status);
    }
    ...
}
```
### DTO в контроллере с заполнением из Request
```php
// в классе DTO
/** @RequestDto */
class СustomDto

// в контроллере
public function index(СustomDto $dto): Response
```
### Валидация
```php
// в контроллере
$this->validate($object, $groups)
```
### Генерация REST-контроллера с поддержкой CRUD
Выполните команду
```
php bin/console make:rest
```
### API-документация
Перейдите по ссылке */docs/*
