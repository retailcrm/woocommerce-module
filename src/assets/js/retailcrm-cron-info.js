jQuery(function () {
    function RetailcrmCronInfo()
    {
        this.title = jQuery('.debug_info_options').get(0)
        this.submitButton = jQuery('button[id="clear_cron_tasks"]').get(0);

        if (typeof this.title  === 'undefined') {
            return false;
        }

        if (typeof this.submitButton === 'undefined') {
            return false;
        }

        this.icml = 0;
        this.history = 0;
        this.inventories = 0;
        this.messageSuccessful = '';
        this.loyalty_upload_price = 0;

        this.adminUrl = AdminUrl.url;

        let _this = this;

        jQuery.ajax({
            url: this.adminUrl + '/admin-ajax.php?action=cron_info',
            method: "POST",
            timeout: 0,
            data: {ajax: 1},
            dataType: "json"
        })
            .done(function (response) {
                _this.history = response.history;
                _this.icml = response.icml;
                _this.inventories = response.inventories;
                _this.messageSuccessful = response.translate.tr_successful;
                _this.loyalty_upload_price = response.loyalty_upload_price

                _this.displayInfoAboutCron(
                    response.translate.tr_td_cron,
                    response.translate.tr_td_icml,
                    response.translate.tr_td_history,
                    response.translate.tr_td_inventories,
                    response.translate.tr_td_loyalty_upload_price
                );
            })

        this.clearCronTasks = this.clearCronTasks.bind(this);

        jQuery(this.submitButton).click(this.clearCronTasks);
    }

    RetailcrmCronInfo.prototype.displayInfoAboutCron = function (cron, icml, history, inventories, loyalty_upload_price) {
        this.table = jQuery(this.title).next();
        this.table.append('<tbody class="retail-debug-info"></tbody>');
        this.infoTable = jQuery('tbody[class="retail-debug-info"]').get(0);

        jQuery(this.infoTable).append("<tr><td class='retail-cron-info-title'>" + cron + " : " + "</td></tr>");
        jQuery(this.infoTable).append("<tr><td>" + icml + "</td><td> " + this.icml + "</td></tr>");
        jQuery(this.infoTable).append("<tr><td>" + history + "</td><td> " + this.history + "</td></tr>");
        jQuery(this.infoTable).append("<tr><td>" + inventories + "</td><td> " + this.inventories + "</td></tr>");
        jQuery(this.infoTable).append("<tr><td>" + loyalty_upload_price + "</td><td>" + this.loyalty_upload_price + "</td></tr>");
    }

    RetailcrmCronInfo.prototype.clearCronTasks = function () {
        let _this = this;

        jQuery.ajax({
            type: "POST",
            url: this.adminUrl + '/admin-ajax.php?action=clear_cron_tasks',
            success: function (response) {
                alert(_this.messageSuccessful);
            }
        });
    };

    window.RetailcrmCronInfo = RetailcrmCronInfo;

    if (!(typeof RetailcrmCronInfo === 'undefined')) {
        new window.RetailcrmCronInfo();
    }
});
