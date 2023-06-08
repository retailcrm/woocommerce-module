jQuery(function () {
    function RetailcrmMetaFields()
    {
        jQuery('.order-meta-data-retailcrm').closest('tr').addClass('retailcrm-hidden')
        jQuery('.customer-meta-data-retailcrm').closest('tr').addClass('retailcrm-hidden')

        jQuery('.customer-meta-data-retailcrm').closest('tr').after('<tr class="retailcrm-insert-select"></tr>')
        this.insertSelects = jQuery('.retailcrm-insert-select').get(0);
        this.orderTextArea = jQuery('.order-meta-data-retailcrm').get(0);
        this.customerTextArea = jQuery('.customer-meta-data-retailcrm').get(0);
        this.indexOrder = 1;
        this.indexCustomer = 1;

        if (typeof this.insertSelects  === 'undefined'
            || this.orderTextArea === 'undefined'
            || this.customerTextArea === 'undefined'
        ) {
            return false;
        }

        jQuery(this.insertSelects).append(
            `<label class="retailcrm-order-label">Custom fields for order</label><div class="retailcrm-order-selects"></div>` +
            `<label class="retailcrm-customer-label">Custom fields for customer</label><div class="retailcrm-customer-selects"></div>` +
            `<button class="add-new-select-retailcrm" id="add-new-select-order-retailcrm">Add new select for order</button>` +
            `<button class="add-new-select-retailcrm" id="add-new-select-customer-retailcrm">Add new select for customer</button>`
        );

        let _this = this;

        if (this.orderTextArea.value === '') {
            this.orderTextArea.value = JSON.stringify({});
        }

        if (this.customerTextArea.value === '') {
            this.customerTextArea.value = JSON.stringify({});
        }

        jQuery.ajax({
            url: AdminUrl.url + '/admin-ajax.php?action=set_meta_fields',
            method: 'POST',
            timeout: 0,
            data: {ajax: 1},
            dataType: 'json'
        })
            .done(function (response) {
                _this.dataFields = response;

                jQuery(".retailcrm-order-label").text(response.translate.tr_lb_order);
                jQuery(".retailcrm-customer-label").text(response.translate.tr_lb_customer);
                jQuery(".add-new-select-retailcrm").text(response.translate.tr_btn);
                jQuery(".add-new-select-retailcrm").text(response.translate.tr_btn);

                if (jQuery.isEmptyObject(JSON.parse(_this.orderTextArea.value)) && jQuery.isEmptyObject(JSON.parse(_this.customerTextArea.value))) {
                    _this.createSelects(_this.dataFields);
                }

                if (!jQuery.isEmptyObject(JSON.parse(_this.orderTextArea.value))) {
                    let objectJson = JSON.parse(_this.orderTextArea.value);

                    _this.indexOrder = _this.restoreSelectsData(
                        objectJson,
                        _this.dataFields.order,
                        'order',
                        1
                    );
                }

                if (!jQuery.isEmptyObject(JSON.parse(_this.customerTextArea.value))) {
                    let objectJson = JSON.parse(_this.customerTextArea.value);

                    _this.indexCustomer = _this.restoreSelectsData(
                        objectJson,
                        _this.dataFields.customer,
                        'customer',
                        1
                    );
                }
            })

        jQuery(this.insertSelects).on('change', '.retailcrm-meta-select', function() {
            let selectsData = {};
            let typeSelect = this.id.split('-')[0]
            let idChangeSelect = this.id.split('-')[2]

            if (this.id.includes('order')) {
                selectsData = _this.getSelectsData(
                    _this.orderTextArea.value,
                    'order',
                    idChangeSelect,
                    typeSelect
                );
            } else {
                selectsData = _this.getSelectsData(
                    _this.customerTextArea.value,
                    'customer',
                    idChangeSelect,
                    typeSelect
                );
            }

            if (Object.keys(selectsData).length != 0) {
                if (this.id.includes('order')) {
                    _this.orderTextArea.value = JSON.stringify(selectsData);
                } else {
                    _this.customerTextArea.value = JSON.stringify(selectsData);
                }
            }
        });

        jQuery(this.insertSelects).on('click', '.add-new-select-retailcrm', function(event) {
            event.preventDefault();

            if (this.id.includes('order')) {
                _this.addPairSelects(
                    '.retailcrm-order-selects',
                    'order',
                    _this.dataFields.order,
                    _this.indexOrder
                );

                _this.indexOrder++;
            } else {
                _this.addPairSelects(
                    '.retailcrm-customer-selects',
                    'customer',
                    _this.dataFields.customer,
                    _this.indexCustomer
                );

                _this.indexCustomer++;
            }
        });

        jQuery(this.insertSelects).on('click', '.delete-select-retailcrm', function(event) {
            event.preventDefault();

            let objectJson = {}
            let entity = this.id.split('-')[0];
            let index = this.id.split('-')[1];

            if (entity === 'order') {
                objectJson = JSON.parse(_this.orderTextArea.value);
            } else {
                objectJson = JSON.parse(_this.customerTextArea.value);
            }

            let metaFiled = jQuery(`#metaFields-${entity}-${index}`);
            let valueMetaField = metaFiled.val();

            for (let i = 1; i <= index; i++) {
                jQuery(`#select-pair-${entity}-${i}`).remove();
            }

            delete objectJson[valueMetaField];

            if (entity === 'order') {
                _this.indexOrder = _this.restoreSelectsData(
                    objectJson,
                    _this.dataFields.order,
                    'order',
                    1
                );

                _this.orderTextArea.value = JSON.stringify(objectJson);
            } else {
                _this.indexCustomer = _this.restoreSelectsData(
                    objectJson,
                    _this.dataFields.customer,
                    'customer',
                    1
                );

                _this.customerTextArea.value = JSON.stringify(objectJson);
            }

            jQuery(`#add-new-select-${entity}-retailcrm`).removeClass('retailcrm-hidden')
        });
    }

    RetailcrmMetaFields.prototype.restoreSelectsData = function (objectJson, dataEntity, entity, index) {
        for (let key in objectJson) {
            let getMetaSelect = `#metaFields-${entity}-${index} option[value=${key}]`;
            let getCustomSelect = `#customFields-${entity}-${index} option[value=${objectJson[key]}]`;

            this.addPairSelects(`.retailcrm-${entity}-selects`, entity, dataEntity, index);

            jQuery(getMetaSelect).prop('selected', true);
            jQuery(getCustomSelect).prop('selected', true);

            index++;
        }

        return index;
    }

    RetailcrmMetaFields.prototype.getSelectsData = function (textarea, entity, idChangeSelect, typeSelect) {
        let _this = this;
        let selectsData = {};

        if (!jQuery.isEmptyObject(textarea)) {
            let objectJson = JSON.parse(textarea);
            let field = jQuery(`#${typeSelect}-${entity}-${idChangeSelect}`);
            let selectedField = jQuery(`#${typeSelect}-${entity}-${idChangeSelect} option:selected`);
            let fieldValue = field.val();

            if (Object.values(objectJson).includes(fieldValue) || fieldValue in objectJson) {
                _this.addWarning(field, selectedField.text());

                // Search key in object by id selects
                let counter = 0;

                for (let key in objectJson) {
                    counter++;

                    if (counter == idChangeSelect) {
                        delete objectJson[key];
                    }
                }

                if (entity === 'order') {
                    _this.orderTextArea.value = JSON.stringify(objectJson);
                } else {
                    _this.customerTextArea.value = JSON.stringify(objectJson);
                }
            }
        }

        for (let i = 1; i <= jQuery(`.retailcrm-${entity}-selects div`).length; i++) {
            let metaFiled = jQuery(`#metaFields-${entity}-${i}`);
            let customField = jQuery(`#customFields-${entity}-${i}`);
            let valueMetaField = metaFiled.val();
            let valueCustomField = customField.val();

            if (valueMetaField === 'default_retailcrm' || valueCustomField === 'default_retailcrm') {
                continue;
            }

            _this.deleteWarning(metaFiled);
            _this.deleteWarning(customField);

            selectsData[valueMetaField] = valueCustomField;
        }

        return selectsData;
    }

    RetailcrmMetaFields.prototype.createSelects = function (dataFields) {
        let orderData = dataFields.order;
        let customerData = dataFields.customer;

        //Create selects for order data
        this.buildElements('.retailcrm-order-selects', 'order', this.indexOrder);
        this.fillSelects(orderData, 'order', this.indexOrder);

        //Create selects for customer data
        this.buildElements('.retailcrm-customer-selects', 'customer', this.indexCustomer);
        this.fillSelects(customerData, 'customer', this.indexCustomer);

        this.indexOrder++;
        this.indexCustomer++;
    }

    RetailcrmMetaFields.prototype.buildElements = function (element, entity, index) {
        jQuery(element).append(
            `<div class="retailcrm-select-pair" id="select-pair-${entity}-${index}">` +
            `<select class="retailcrm-meta-select" id="metaFields-${entity}-${index}"></select>` +
            `<select class="retailcrm-meta-select" id="customFields-${entity}-${index}"></select>` +
            `<button class="delete-select-retailcrm" id="${entity}-${index}"></button></div>`
        );

        jQuery(`#add-new-select-${entity}-retailcrm`).insertAfter(`#select-pair-${entity}-${index}`);
    }

    RetailcrmMetaFields.prototype.fillSelects = function (data, entity, index) {
        if (Object.keys(data.meta).length - 1 <= index || Object.keys(data.custom).length - 1 <= index) {
            if (entity === 'order') {
                jQuery('#add-new-select-order-retailcrm').addClass('retailcrm-hidden')
            } else {
                jQuery('#add-new-select-customer-retailcrm').addClass('retailcrm-hidden')
            }
        }

        // Set meta fields in select
        jQuery.each(data.meta, function(key, value) {
            jQuery(`#metaFields-${entity}-${index}`)
                .append(jQuery('<option></option>')
                    .attr('value', key)
                    .text(value));
        });

        // Set custom fields in select
        jQuery.each(data.custom, function(key, value) {
            jQuery(`#customFields-${entity}-${index}`)
                .append(jQuery('<option></option>')
                    .attr('value', key)
                    .text(value));
        });

        jQuery(`#customFields-${entity}-${index}`)
            .append(jQuery(`<optgroup id=default-${entity}-${index}-crm-fields label = '${data.tr_default_crm_fields}'></optgroup>`));

        jQuery.each(data.crmDefault, function(key, value) {
            jQuery(`#default-${entity}-${index}-crm-fields`)
                .append(jQuery('<option></option>')
                    .attr('value', key)
                    .text(value));
        });
    }

    RetailcrmMetaFields.prototype.addPairSelects = function (element, entity, data, index) {
        this.buildElements(element, entity, index);
        this.fillSelects(data, entity, index);
    }

    RetailcrmMetaFields.prototype.addWarning = function (field, fieldValue) {
        alert(fieldValue + ' already been selected');

        field.prop('value', 'default_retailcrm');
        field.addClass('red-selected-retailcrm');
    }

    RetailcrmMetaFields.prototype.deleteWarning = function (field) {
        if (field.css('border-color') === "rgb(255, 0, 0)") {
            field.removeClass('red-selected-retailcrm')
        }
    }

    window.RetailcrmMetaFields = RetailcrmMetaFields;

    if (typeof RetailcrmMetaFields !== 'undefined') {
        new window.RetailcrmMetaFields();
    }
});
