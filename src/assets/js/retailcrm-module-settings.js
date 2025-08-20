jQuery(function () {
    if (document.querySelector('#woocommerce_integration-retailcrm_bind_by_sku')) {
        document.querySelector('#woocommerce_integration-retailcrm_bind_by_sku').onchange = function() {
            let useXmlId = this.checked ? 'yes' : 'no';

            document.querySelector('.submit').onmousedown = function() {
                jQuery.ajax({
                    url: RetailcrmAdminUrl.url + '/admin-ajax.php?action=retailcrm_generate_icml',
                    method: 'POST',
                    timeout: 0,
                    data: {useXmlId: useXmlId, _ajax_nonce: RetailcrmAdminUrl.nonce},
                    dataType: 'json'
                })
            }
        };
    }
})
