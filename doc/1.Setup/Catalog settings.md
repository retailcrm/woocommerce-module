### Настройки каталога

В версии 4.4.4 добавлен функционал передачи описания товара в каталог. В настройках каталога необходимо выбрать, какое описание передавать краткое или полное. По умолчанию передается полное описание товара.

Поле description(описание) выводится в карточке товара, так же его можно использовать в twig-шаблонах. Например, для вывода в печатных формах.
Пример получения описание торгового предложения:
```twig
{% for availableOrderProduct in order.availableOrderProducts %} {{ availableOrderProduct.getOffer().getDescription() }} {% endfor %}
```

В настройке представлены статусы товаров в WooCommerce *(Товары -> карточка товара -> блок Опубликовано)*. Из товаров, чей статус будет соответствовать выбранному, будет сгенерирован ICML-файл каталога. Для выбора необходимо поставить галочку напротив нужного статуса и сохранить настройки.

Статус видимости товара Личное, также относится к статусам товара "Статус: Опубликовано как личное". Анализ статусов товара был произведен в задаче [#76054](https://redmine.retailcrm.tech/issues/76054)

![](https://lh3.googleusercontent.com/A64aLvFUecO7kd73gEH0SbfQsYkhjDfOl0DRmcx6FsMfAWX7Z5DFX_Y5_lHnm7z3D3SpKzNHOFINI26mlihBNbqsuV_8Kd0S3QOqWt32Pv2AvrDWJQc44eG03J5wkyz2VL3BXV06=s0)![](https://lh6.googleusercontent.com/aG6m6-TGpU4kWPIVeMQ_EfN1kBsG0l3ISVRx9CU1KlvZdZ4n8NkhkM-DFLZctmQXqKi65Hv83paSZf9jK1mCj7QWCUn1syfvBme8kjYGzPBHH-3feSJE-G8dKtLTBqwvER4RbLON=s0)
