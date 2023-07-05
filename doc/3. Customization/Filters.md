### Фильтры

Если вы хотите изменить данные отправляемые между CRM и CMS, вы можете использовать **пользовательские фильтры**.

Чтобы использовать фильтры, необходимо в директории wp-content создать директорию mu-plugins и в ней создать кастомный файл mu-simla.php. 

### Список доступных фильтров

> retailcrm_process_customer - позволяет изменить данные клиента при передачи из CMS -> CRM.

> retailcrm_process_customer_address - позволяет изменить адрес клиента при передачи из CMS -> CRM.

> retailcrm_process_customer_corporate - позволяет изменить данные корпоративного клиента при передачи из CMS -> CRM.

> retailcrm_process_customer_corporate_address - позволяет изменить адрес корпоративного клиента при передачи из CMS -> CRM.

> retailcrm_process_customer_corporate_company - позволяет изменить компанию корпоративного клиента при передачи из CMS -> CRM.

> retailcrm_customer_roles - позволяет изменить допустимые роли клиентов.

> retailcrm_daemon_collector - позволяет изменить данные для Daemon Collector.

> retailcrm_initialize_analytics - позволяет изменить данные скрипта для Google Analytics.

> retailcrm_send_analytics - позволяет изменить отправляемые данные Google Analytics.

> retailcrm_process_customer_custom_fields -  позволяет изменить данные кастомных полей клиента при передачи из CRM -> CMS  .

> retailcrm_history_before_save - позволяет изменить данные заказа и клиента при передачи из CRM -> CMS.

> retailcrm_process_order_custom_fields - позволяет изменить данные кастомных полей заказ при передачи из CRM -> CMS.

> retailcrm_coupon_order - позволяет изменить данные кастомного поля купона при передачи из CRM -> CMS.

> retailcrm_process_offer -  позволяет изменить данные товара перед записью в ICML каталог.

> retailcrm_process_order - позволяет изменить данные заказа при передачи из CMS -> CRM.

> retailcrm_process_order_address - позволяет изменить адрес заказа при передачи из CMS -> CRM.

> retailcrm_add_cron_interval - позволяет добавить пользовательский интервал времени.

> retailcrm_cron_schedules - позволяет изменить интервал времени для cron задач.

> retailcrm_shipping_list - позволяет изменить методы доставки с CMS.

**Пример использования:**
```php
<?php

add_action('retailcrm_process_offer', 'changeProductInfo', 10, 2);

function changeProductInfo($productData, $wcProduct)
{
    $productData['name'] .= 'Test';

    return $productData;
}
```
