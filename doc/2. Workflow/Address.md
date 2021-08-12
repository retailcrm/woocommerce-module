# Address processing

С версии `4.3.8` изменена логика работы с адресами.

В заказ CRM c заказа WooCommerce будет передаваться, только shipping адрес. Если при оформлении заказа указан только billing адрес, то WooCommerce записывает в БД эти же данные в shipping, то есть shipping = billing.

При создании обычных/корпоративных клиентов в CRM будет передаваться billing адрес с заказа/пользователя WooCommerce. Если клиент гость и у него нет данных по billing адресу, тогда будет передан billing адрес заказа.

Для кастомизаций адресов в CRM, добавили новые фильтры:
* `retailcrm_process_order_address`
* `retailcrm_process_customer_address`
* `retailcrm_process_customer_corporate_address`

## Пример работы фильтров
В приведенном ниже примере показано, как возможно кастомизировать адрес заказа:

```php
<?php

add_filter('retailcrm_process_order_address', 'editOrderAddress', 10, 2);

function editOrderAddress($address, $order)
{
    $address['text'] = 'Test';
    
    return $address;
}
```