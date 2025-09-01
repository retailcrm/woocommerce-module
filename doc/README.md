# Developers documentation


## 1 Правила разработки
При доработке модуля необходимо придерживаться следующих правил:

### 1.1. Хуки и фильтры
- При создании **новых хуков, фильтров, AJAX-хуков** добавляйте префикс, который идентифицирует принадлежность к плагину.
- Используемый префикс: `retailcrm_`
- **Пример правильного формата**:
  ```php
  wp_ajax_retailcrm_do_upload
  ```
  
### 1.2 Внешние источники
Запрещается получать файлы (JSON, изображения и т.п.) из удалённых источников.
Вместо этого — добавлять их в сам плагин.

### 1.3 Переводы
- Всегда используйте идентичный идентификатор перевода `woo-retailcrm`:
  ```php
  __('I agree to receive promotional newsletters', 'woo-retailcrm')
  ```
- Не подставлять переменные напрямую в перевод а использовать `sprintf()` или `printf()`:
    ```php
    __($test, 'woo-retailcrm') // запрещено
  
    printf( // Разрешено
        /* translators: %s: First name of the user */
        esc_html__( 'Hello %s, how are you?', 'woo-retailcrm' ),
        esc_html( $user_firstname )
    );
  
    sprintf( // если параметров больше одного, используйте нумерацию
        'Fill order data: WC_Customer ID: %1$s email: %2$s WC_Order ID: %3$s',
        $wcCustomerId,
        $wcCustomerEmail,
        $wcOrder->get_id()
    )
    ```
  
### 1.4 Экранирование переводов
Не использовать стандартную функцию `__` без экранирования. Вместо этого:
  ```php
  esc_html__() // Экранирование html тегов
  esc_attr__() // Экранирование аттрибутов html элементов
  ```

### 1.5 Экранирование вывода данных
При выводе данных (например, с помощью `echo`) необходимо выполнять экранирование с помощью методов:
```php
esc_html() // Экранирование html тегов
esc_attr() // Экранирование аттрибутов html элементов
esc_js() // Экранирование js
```
### 1.6. Очистка входящих данных
- При использовании `filter_input()` обязательно указывать третий параметр — предполагаемый тип значения:
  ```php
  filter_input(INPUT_POST, 'Step', FILTER_SANITIZE_NUMBER_INT);
  ```
- При получении параметров через `$_GET`, `$_POST`, `$_REQUEST`:
  ```php
  $test = wp_unslash($_GET['value']); // Удаление слешей
  sanitize_text_field($_GET['value']); // удаление параметра
  ```
### 1.7 AJAX-запросы
- Обязательно генерировать `nonce-токены`:
  ```php
  $token = wp_create_nonce('woo-retailcrm-admin-nonce'); // пример названия для токена. Должен быть уникальным для каждой логической цепочки.
  ```
- Проверять `nonce-токен` при получении запроса:
  ```php
  heck_ajax_referer('woo-retailcrm-admin-nonce', '_ajax_nonce', false); // _ajax_nonce - базовый ключ параметра WordPress, в котором хранится nonce-токен
  ```
- Если запрос требует прав `администратора/менеджера` для выполнения действий, производить проверку:
  ```php
  current_user_can('manage_woocommerce');
  current_user_can('manage_options');
  ```
  
## 2 Проверка плагина после доработки
Для проверки используйте плагин: [WordPress Plugin Check](https://wordpress.org/plugins/plugin-check/) (Устанавливается как плагин в WordPress)
- Исправьте все несоответствия с тегом `ERROR`
- Сообщения с тегом `WARNING` — исправлять по возможности
