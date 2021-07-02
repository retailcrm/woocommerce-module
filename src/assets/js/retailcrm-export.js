jQuery(function () {
    function RetailcrmExportForm()
    {
        this.submitButton = jQuery('button[id="export-orders-submit"]').get(0);

        jQuery(this.submitButton).after('<div id="export-orders-progress" class="retail-progress retail-hidden"></div');
        jQuery(this.submitButton).before('<div id="export-orders-count" class="retail-count-data-upload"></div');

        this.progressBar = jQuery('div[id="export-orders-progress"]').get(0);

        if (typeof this.submitButton === 'undefined') {
            return false;
        }

        if (typeof this.progressBar === 'undefined') {
            return false;
        }

        jQuery(this.submitButton).addClass('retail-hidden');

        this.ordersCount = 0;
        this.customersCount = 0;
        let _this = this;

        jQuery.ajax({
            url: window.location.origin + '/wp-admin/admin-ajax.php?action=content_upload',
            method: "POST",
            timeout: 0,
            data: {ajax: 1},
            dataType: "json"
        })
            .done(function (response) {
                _this.ordersCount = response.count_orders;
                _this.customersCount = response.count_users;
                jQuery(_this.submitButton).removeClass('retail-hidden');

                _this.displayCountUploadData();
            })

        this.isDone = false;
        this.ordersStepSize = 50;
        this.customersStepSize = 50;
        this.ordersStep = 0;
        this.customersStep = 0;
        this.displayCountUploadData = this.displayCountUploadData.bind(this);
        this.submitAction = this.submitAction.bind(this);
        this.exportAction = this.exportAction.bind(this);
        this.exportDone = this.exportDone.bind(this);
        this.initializeProgressBar = this.initializeProgressBar.bind(this);
        this.updateProgressBar = this.updateProgressBar.bind(this);

        jQuery(this.submitButton).click(this.submitAction);
    }

    RetailcrmExportForm.prototype.displayCountUploadData = function () {
        this.counter = jQuery('div[id="export-orders-count"]').get(0);
        jQuery(this.counter).text('Customers: ' + this.customersCount + ' Orders: ' + this.ordersCount);
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
            data.RETAILCRM_EXPORT_CUSTOMERS_STEP = this.customersStep;
            data.Entity = 'customer';
            this.customersStep++;
        } else {
            if (this.ordersStep * this.ordersStepSize < this.ordersCount) {
                data.RETAILCRM_EXPORT_ORDERS_STEP = this.ordersStep;
                data.Entity = 'order';
                this.ordersStep++;
            } else {
                data.RETAILCRM_UPDATE_SINCE_ID = 1;
                this.isDone = true;
            }
        }

        let _this = this;

        jQuery.ajax({
            url: window.location.origin + '/wp-admin/admin-ajax.php?action=do_upload',
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
        jQuery(this.submitButton).addClass('retail-hidden');
        jQuery(this.progressBar)
            .removeClass('retail-hidden')
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
        alert('Done');
    }

    window.RetailcrmExportForm = RetailcrmExportForm;

    if (!(typeof RetailcrmExportForm === 'undefined')) {
        new window.RetailcrmExportForm();
    }
});
