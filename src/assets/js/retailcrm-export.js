jQuery(function () {
    function RetailcrmExportForm()
    {
        this.submitButton = jQuery('button[id="export-orders-submit"]').get(0);
        this.selectedOrdersButton = jQuery('button[id="export_selected_orders_btn"]').get(0);

        jQuery(this.submitButton).after('<div id="export-orders-progress" class="retail-progress retailcrm-hidden"></div');
        jQuery(this.submitButton).before('<div id="export-orders-count" class="retail-count-data-upload"></div');

        this.progressBar = jQuery('div[id="export-orders-progress"]').get(0);

        if (typeof this.submitButton === 'undefined') {
            return false;
        }

        if (typeof this.selectedOrdersButton === 'undefined') {
            return false;
        }

        if (typeof this.progressBar === 'undefined') {
            return false;
        }

        jQuery(this.submitButton).addClass('retailcrm-hidden');

        this.ordersCount = 0;
        this.customersCount = 0;

        this.adminUrl = AdminUrl.url;

        let _this = this;

        jQuery.ajax({
            url: this.adminUrl + '/admin-ajax.php?action=content_upload',
            method: "POST",
            timeout: 0,
            data: {ajax: 1},
            dataType: "json"
        })
            .done(function (response) {
                _this.ordersCount = Number(response.count_orders);
                _this.customersCount = Number(response.count_users);
                jQuery(_this.submitButton).removeClass('retailcrm-hidden');


                _this.messageEmtyField = response.translate.tr_empty_field;
                _this.messageSuccessful = response.translate.tr_successful;

                _this.displayCountUploadData(response.translate.tr_order, response.translate.tr_customer);
            })

        this.isDone = false;
        this.ordersStepSize = 50;
        this.customersStepSize = 50;
        this.ordersStep = 0;
        this.customersStep = 0;
        this.displayCountUploadData = this.displayCountUploadData.bind(this);
        this.submitAction = this.submitAction.bind(this);
        this.actionExportSelectedOrders = this.actionExportSelectedOrders.bind(this);
        this.exportAction = this.exportAction.bind(this);
        this.exportDone = this.exportDone.bind(this);
        this.initializeProgressBar = this.initializeProgressBar.bind(this);
        this.updateProgressBar = this.updateProgressBar.bind(this);

        jQuery(this.submitButton).click(this.submitAction);
        jQuery(this.selectedOrdersButton).click(this.actionExportSelectedOrders);
    }

    RetailcrmExportForm.prototype.displayCountUploadData = function (order, customer) {
        this.counter = jQuery('div[id="export-orders-count"]').get(0);


        jQuery(this.counter).text(`${customer}: ${this.customersCount} ${order}: ${this.ordersCount}`);
    }

    RetailcrmExportForm.prototype.submitAction = function (event) {
        event.preventDefault();

        jQuery(this.counter).css("margin-left", "120px");

        this.initializeProgressBar();
        this.exportAction();
    };

    RetailcrmExportForm.prototype.exportAction = function () {

        let data = {};

        if (this.customersStep * this.customersStepSize < this.customersCount) {
            data.Step = this.customersStep;
            data.Entity = 'customer';
            this.customersStep++;
        } else {
            if (this.ordersStep * this.ordersStepSize < this.ordersCount) {
                data.Step = this.ordersStep;
                data.Entity = 'order';
                this.ordersStep++;
            } else {
                data.RETAILCRM_UPDATE_SINCE_ID = 1;
                this.isDone = true;
            }
        }

        let _this = this;

        jQuery.ajax({
            url: this.adminUrl + '/admin-ajax.php?action=do_upload',
            method: "POST",
            timeout: 0,
            data: data
        })
            .done(function (response) {
                if (_this.isDone) {
                    return _this.exportDone();
                }

                _this.updateProgressBar();
                _this.exportAction();
            })
    };

    RetailcrmExportForm.prototype.initializeProgressBar = function () {
        jQuery(this.submitButton).addClass('retailcrm-hidden');
        jQuery(this.progressBar)
            .removeClass('retailcrm-hidden')
            .append(jQuery('<div/>', {class: 'retail-progress__loader', text: '0%'}))

        window.addEventListener('beforeunload', this.confirmLeave);
    };

    RetailcrmExportForm.prototype.updateProgressBar = function () {
        let processedOrders = this.ordersStep * this.ordersStepSize;

        if (processedOrders > this.ordersCount) {
            processedOrders = this.ordersCount;
        }

        let processedCustomers = this.customersStep * this.customersStepSize;

        if (processedCustomers > this.customersCount) {
            processedCustomers = this.customersCount;
        }

        const processed = processedOrders + processedCustomers;
        const total = this.ordersCount + this.customersCount;
        const percents = Math.round(100 * processed / total);

        jQuery(this.progressBar).find('.retail-progress__loader').text(percents + '%');
        jQuery(this.progressBar).find('.retail-progress__loader').css('width', percents + '%');
        jQuery(this.progressBar).find('.retail-progress__loader').attr('title', processed + '/' + total);
    };

    RetailcrmExportForm.prototype.confirmLeave = function (event) {
        event.preventDefault();
        event.returnValue = 'Export process has been started';
    }

    RetailcrmExportForm.prototype.exportDone = function () {
        window.removeEventListener('beforeunload', this.confirmLeave);
        alert(this.messageSuccessful);
    }

    RetailcrmExportForm.prototype.actionExportSelectedOrders = function () {
        let ids = jQuery('#woocommerce_integration-retailcrm_export_selected_orders_ids').val();

        if (ids === '') {
            alert(this.messageEmtyField);
        } else {
            let _this = this;

            jQuery.ajax({
                type: "POST",
                url: this.adminUrl + '/admin-ajax.php?action=upload_selected_orders&order_ids_retailcrm=' + ids,
                success: function (response) {
                    alert(_this.messageSuccessful);
                }
            });
        }
    };

    window.RetailcrmExportForm = RetailcrmExportForm;

    if (!(typeof RetailcrmExportForm === 'undefined')) {
        new window.RetailcrmExportForm();
    }
});
