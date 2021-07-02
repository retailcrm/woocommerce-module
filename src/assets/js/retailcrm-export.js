jQuery(function () {
    function RetailcrmExportForm() {
        this.submitButton = jQuery('button[id="export-orders-submit"]').get(0);
        jQuery(this.submitButton).after('<div id="export-orders-progress" class="retail-progress retail-hidden"></div');
        this.progressBar = jQuery('div[id="export-orders-progress"]').get(0);

        if (typeof this.submitButton === 'undefined') {
            return false;
        }

        if (typeof this.progressBar === 'undefined') {
            return false;
        }

        this.isDone = false;
        this.ordersCount = 100;//parseInt(jQuery(this.form).find('input[name="RETAILCRM_EXPORT_ORDERS_COUNT"]').val());
        this.customersCount = 100;//parseInt(jQuery(this.form).find('input[name="RETAILCRM_EXPORT_CUSTOMERS_COUNT"]').val());
        this.ordersStepSize = 50;// parseInt(jQuery(this.form).find('input[name="RETAILCRM_EXPORT_ORDERS_STEP_SIZE"]').val());
        this.customersStepSize = 50;//parseInt(jQuery(this.form).find('input[name="RETAILCRM_EXPORT_CUSTOMERS_STEP_SIZE"]').val());
        this.ordersStep = 0;
        this.customersStep = 0;

        this.submitAction = this.submitAction.bind(this);
        this.exportAction = this.exportAction.bind(this);
        this.exportDone = this.exportDone.bind(this);
        this.initializeProgressBar = this.initializeProgressBar.bind(this);
        this.updateProgressBar = this.updateProgressBar.bind(this);

        jQuery(this.submitButton).click(this.submitAction);
    }

    RetailcrmExportForm.prototype.submitAction = function (event) {
        event.preventDefault();
        console.log('Step2');
        this.initializeProgressBar();
        this.exportAction();
    };

    RetailcrmExportForm.prototype.exportAction = function () {
        console.log('Step3');
        let data = {
            submitretailcrm: 1,
            ajax: 1
        };
        console.log(this.ordersStep);

        if (this.ordersStep * this.ordersStepSize < this.ordersCount) {
            data.RETAILCRM_EXPORT_ORDERS_STEP = this.ordersStep;
            this.ordersStep++;
        } else {
            if (this.customersStep * this.customersStepSize < this.customersCount) {
                data.RETAILCRM_EXPORT_CUSTOMERS_STEP = this.customersStep;
                this.customersStep++;
            } else {
                data.RETAILCRM_UPDATE_SINCE_ID = 1;
                this.isDone = true;
            }
        }

        let _this = this;
        console.log(data);
        jQuery.ajax({
            url: window.location.origin + '/wp-admin/admin-ajax.php?action=do_upload',
            method: "POST",
            timeout: 0,
            data: data
        })
            .done(function (response) {
                if(_this.isDone) {
                    return _this.exportDone();
                }

                _this.updateProgressBar();
                _this.exportAction();
            })
    };

    RetailcrmExportForm.prototype.initializeProgressBar = function () {
        console.log('Step4');
        jQuery(this.submitButton).addClass('retail-hidden');
        jQuery(this.progressBar)
            .removeClass('retail-hidden')
            .append(jQuery('<div/>', {class: 'retail-progress__loader', text: '0%'}))

        window.addEventListener('beforeunload', this.confirmLeave);
    };

    RetailcrmExportForm.prototype.updateProgressBar = function () {
        console.log('Step5');
        let processedOrders = this.ordersStep * this.ordersStepSize;
        if (processedOrders > this.ordersCount)
            processedOrders = this.ordersCount;

        let processedCustomers = this.customersStep * this.customersStepSize;
        if (processedCustomers > this.customersCount)
            processedCustomers = this.customersCount;

        const processed = processedOrders + processedCustomers;
        const total = this.ordersCount + this.customersCount;
        const percents = Math.round(100 * processed / total);

        jQuery(this.progressBar).find('.retail-progress__loader').text(percents + '%');
        jQuery(this.progressBar).find('.retail-progress__loader').css('width', percents + '%');
        jQuery(this.progressBar).find('.retail-progress__loader').attr('title', processed + '/' + total);
    };

    RetailcrmExportForm.prototype.confirmLeave = function (event) {
        console.log('Step6');
        event.preventDefault();
        e.returnValue = 'Export process has been started';
    }

    RetailcrmExportForm.prototype.exportDone = function () {
        console.log('Step7');
        window.removeEventListener('beforeunload', this.confirmLeave);
        alert('Done')
    }

    window.RetailcrmExportForm = RetailcrmExportForm;
    console.log('before');
    if (!(typeof RetailcrmExportForm === 'undefined')) {
        new window.RetailcrmExportForm();
    }
    console.log('after');
});
