# Symfony api-skeleton
Шаблон API для Symfony.
## Особенности
- класс *PropertyFiller* для заполнения полей объектов из массива
- автоматический парсинг входящего http-запроса в DTO-объект в параметре действия контроллера
- контроллер *BaseController* с методами *success()* и *error()* с поддержкой сериализации и методом *validate()* для валидации
- сквозная обработка ошибок для http-запросов
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
### Заполнение полей объекта из массива
```php
// в классе
class СustomClass
{
    ...
    /** @ParamType(type=OtherClass::class) */
    public function setField(OtherClass $value): self

    /** @ParamArrayType(type=OtherClass::class) */
    public function setArrayField(array $values): self
    ...
}

// в коде
$obj = PropertyFiller::create(СustomClass::class, $arrayOfFields)
```
### DTO в контроллере с заполнением из Request
```php
// в классе DTO
/** @Dto */
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
