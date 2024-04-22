jQuery(function () {
    document.querySelector('#woocommerce_integration-retailcrm_bind_by_sku').onchange = function() {
        let useXmlId = this.checked ? 'yes' : 'no';

        document.querySelector('.submit').onclick = function() {
            jQuery.ajax({
                url: AdminUrl.url + '/admin-ajax.php?action=generate_icml',
                method: 'POST',
                timeout: 0,
                data: {useXmlId: useXmlId},
                dataType: 'json'
            })
        }
    };
})
