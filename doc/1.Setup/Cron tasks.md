### Настройки cron задач

В версии 4.4.5 добавлен функционал для изменения интервалов времени выполнения cron задач. 

Для изменения интервала времени необходимо с помощью фильтра **retailcrm_add_cron_interval** добавить пользовательский интервал. Затем изменить интервал для cron задач с помощью фильтра **retailcrm_cron_schedules**.
Кастомизацию необходимо добавить на сервере в директорию wp-content -> mu-plugins -> mu-simla.php. После добавления кастомизации в настройках модуля необходимо очистить старые cron задачи. 
Перейдите в настройки, откройте "Отладочная информация" и нажмите на кнопку "Очистить cron задачи", появится окно с сообщением об успешной очистке, интервалы будут применены. 

Если необходимо вернуть стандартные интервалы, то удаляем кастомизацию и в настройках так же очищаем старые cron задачи.

**Интервалы по умолчанию:**
```php
[
    'icml' => 'three_hours',
    'history' => 'five_minutes',
    'inventories' => 'fiveteen_minutes',
]
```
> Важно! При использовании фильтра **retailcrm_cron_schedules**, можно использовать ключи: 'icml', 'history', 'inventories'.

**Фильтры:**

> retailcrm_add_cron_interval - позволяет добавить пользовательский интервал времени.

> retailcrm_cron_schedules    - позволяет изменить интервал времени для cron задач. 

**Пример использования:**
```php
<?php

add_filter('retailcrm_add_cron_interval', 'add_cron_interval');

function add_cron_interval($schedules)
{
    return ['two_minutes' => [
        'interval' => 120, // seconds
        'display'  => __('Every 2 minutes')
        ]
    ];
}


add_filter('retailcrm_cron_schedules', 'change_cron_tasks');

function change_cron_tasks($cronTasks)
{
    $cronTasks['history'] = 'two_minutes';

    return $cronTasks;
}
```


