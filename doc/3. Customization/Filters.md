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

> retailcrm_process_offer -  позволяет изменить данные товара перед записью в ICML каталог.

> retailcrm_process_order - позволяет изменить данные заказа при передачи из CMS -> CRM.

> retailcrm_process_order_address - позволяет изменить адрес заказа при передачи из CMS -> CRM.

> retailcrm_add_cron_interval - позволяет добавить пользовательский интервал времени.

> retailcrm_cron_schedules - позволяет изменить интервал времени для cron задач.

> retailcrm_shipping_list - позволяет изменить методы доставки с CMS.

> retailcrm_order_create_after - позволяет проверить создание заказа и произвести дополнительные действия 

> retailcrm_order_update_after - позволяет проверить изменение заказа и произвести дополнительные действия

> retailcrm_change_default_meta_fields - позволяет изменить список получаемых по умолчанию мета-полей

> woo_retailcrm_default_order_fields - позволяет изменить список стандартных полей заказа CRM для сопоставления с пользовательскими полями CMS

> woo_retailcrm_default_order_fields - позволяет изменить список стандартных полей клиента CRM для сопоставления с пользовательскими полями CMS
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

**Пример расширения списка стандартных полей CRM с помощью фильтров**
```php
<?php

add_filter('woo_retailcrm_default_customer_fields', function ($fields) {

    $fields['default-crm-field#birthday'] = 'birthday';
    $fields['default-crm-field#gender']   = 'gender';
    $fields['default-crm-field#vip']      = 'vip';

    return $fields;
});
```

Для добавления нового поля необходимо после символа *#* в названии ключа элемента корректно указать символьный код данного поля в CRM. Получить его можно из [Справочника методов API](https://docs.retailcrm.ru/Developers/API/APIMethods#post--api-v5-customers-create)

Если тип данного поля в CRM отличается от числового или строчного, например, дата или время, то необходимо заранее преобразовать значение из поля в CMS в корректный формат.

Пример корректного преобразования в тип DateTime для передачи даты:

```php

if (isset($crmField[1]) && $crmField[1] === 'birthday') {
   	if (!$metaValue instanceof DateTime) {
		$metaValue = DateTime::createFromFormat('Y-m-d', $metaValue);

		if (!$metaValue) {
			WC_Retailcrm_Logger::error(
				__METHOD__,
				'Incorrect format. Birthday must be DateTime or date-convertable'
			);

			continue;
		}
	}
}
```

В данном случае выполняется преобразование формата для поля *birthday* для передачи даты рождения в карточку клиента.

Форматирование и передача стандартных полей клиента выполняется в классе **class-wc-retailcrm-customers** в методе **processCustomer**. Для полей заказа в методе **processOrder** класса **class-wc-retailcrm-orders**


