﻿# Тестовая задача  junior 

## Взаимодействие с AmoCRM

## Подключение
```php
require(__DIR__ . '/vendor/autoload.php');
use IntrovertTest\IntrovertTest;
$test=new IntrovertTest('2025-01-01','2025-05-10');
echo $test->renderHtml();
```

## Зависимости проекта
Основные используемые пакеты:
- [Introvert SDK](https://bitbucket.org/mahatmaguru/intr-sdk-test) - для работы с API

## Результат скрипта
![Скриншот](docs/screen/img.png)

## Установка в docker
   ```docker 
   docker-compose up -d
   ```
