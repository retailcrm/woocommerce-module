jQuery(function () {
    function RetailcrmCronInfo()
    {
        this.title = jQuery('.debug_info_options').get(0)

        if (typeof this.title  === 'undefined') {
            return false;
        }

        this.history = 0;
        this.icml = 0;
        this.inventories = 0;

        let _this = this;

        jQuery.ajax({
            url: window.location.origin + '/wp-admin/admin-ajax.php?action=cron_info',
            method: "POST",
            timeout: 0,
            data: {ajax: 1},
            dataType: "json"
        })
            .done(function (response) {
                _this.history = response.history;
                _this.icml = response.icml;
                _this.inventories = response.inventories;

                _this.displayInfoAboutCron(
                    response.translate.tr_td_cron,
                    response.translate.tr_td_icml,
                    response.translate.tr_td_history,
                    response.translate.tr_td_inventories
                );
            })
    }

    RetailcrmCronInfo.prototype.displayInfoAboutCron = function (cron, icml, history, inventories) {
        this.table = jQuery(this.title).next();
        this.table.append('<tbody class="retail-debug-info"></tbody>');
        this.infoTable = jQuery('tbody[class="retail-debug-info"]').get(0);

        jQuery(this.infoTable).append("<tr><td class='retail-cron-info-title'>" + cron + " : " + "</td></tr>");
        jQuery(this.infoTable).append("<tr><td class='retail-cron-info'>" + icml + " : " +  this.icml +  "</td></tr>");
        jQuery(this.infoTable).append("<tr><td class='retail-cron-info'>" + history +  " : " + this.history +  "</td></tr>");
        jQuery(this.infoTable).append("<tr><td class='retail-cron-info'>" + inventories + " : " + this.inventories +  "</td></tr>");
    }

    window.RetailcrmCronInfo = RetailcrmCronInfo;

    if (!(typeof RetailcrmCronInfo === 'undefined')) {
        new window.RetailcrmCronInfo();
    }
});
