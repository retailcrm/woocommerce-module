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
        this.loyaltyUploadPrice = 0;

        this.adminUrl = RetailcrmAdmin.url;

        let _this = this;

        jQuery.ajax({
            url: this.adminUrl + '/admin-ajax.php?action=retailcrm_cron_info',
            method: "POST",
            timeout: 0,
            data: {ajax: 1, _ajax_nonce: RetailcrmAdmin.nonce},
            dataType: "json"
        })
            .done(function (response) {
                if (response.error) {
                    alert(response.error);

                    return false;
                }

                _this.history = response.history;
                _this.icml = response.icml;
                _this.inventories = response.inventories;
                _this.messageSuccessful = response.translate.tr_successful;
                _this.loyaltyUploadPrice = response.loyaltyUploadPrice

                _this.displayInfoAboutCron(
                    response.translate.tr_td_cron,
                    response.translate.tr_td_icml,
                    response.translate.tr_td_history,
                    response.translate.tr_td_inventories,
                    response.translate.tr_td_loyaltyUploadPrice
                );
            })

        this.clearCronTasks = this.clearCronTasks.bind(this);

        jQuery(this.submitButton).click(this.clearCronTasks);
    }

    RetailcrmCronInfo.prototype.displayInfoAboutCron = function (cron, icml, history, inventories, loyaltyUploadPrice) {
        this.table = jQuery(this.title).next();
        this.table.append('<tbody class="retail-debug-info"></tbody>');
        this.infoTable = jQuery('tbody[class="retail-debug-info"]').get(0);

        jQuery(this.infoTable).append("<tr><td class='retail-cron-info-title'>" + cron + " : " + "</td></tr>");
        jQuery(this.infoTable).append("<tr><td>" + icml + "</td><td> " + this.icml + "</td></tr>");
        jQuery(this.infoTable).append("<tr><td>" + history + "</td><td> " + this.history + "</td></tr>");
        jQuery(this.infoTable).append("<tr><td>" + inventories + "</td><td> " + this.inventories + "</td></tr>");
        jQuery(this.infoTable).append("<tr><td>" + loyaltyUploadPrice + "</td><td>" + this.loyaltyUploadPrice + "</td></tr>");
    }

    RetailcrmCronInfo.prototype.clearCronTasks = function () {
        let _this = this;

        jQuery.ajax({
            type: "POST",
            url: this.adminUrl + '/admin-ajax.php?action=retailcrm_clear_cron_tasks',
            data: {_ajax_nonce: RetailcrmAdmin.nonce},
            success: function (response) {
                if (response.error) {
                    alert(response.error);

                    return false;
                }

                alert(_this.messageSuccessful);
            }
        });
    };

    window.RetailcrmCronInfo = RetailcrmCronInfo;

    if (!(typeof RetailcrmCronInfo === 'undefined')) {
        new window.RetailcrmCronInfo();
    }
});
