let cartListenersInitialized = false;

function startTrack(...trackerEvents)
{
    try {
        if (trackerEvents.includes('page_view')) {
            sendProductView();
        }

        if (trackerEvents.includes('open_cart')) {
            sendCartView()
        }

        if (trackerEvents.includes('cart')) {
            if (!cartListenersInitialized) {
                jQuery(document.body).on('added_to_cart updated_cart_totals', sendCartChange);
            }

            jQuery(document.body).on('click', '.single_add_to_cart_button', function ()  {
                sessionStorage.setItem('click_single__add_to_cart_button', '1');
            });

            if (sessionStorage.getItem('click_single__add_to_cart_button') === '1') {
                sessionStorage.removeItem('click_single__add_to_cart_button');

                sendCartChange();
            }

            cartListenersInitialized = true;
        }
    } catch (error) {
        console.error('Ошибка при выполнении трекинга данных', error)
    }

    function sendProductView()
    {
        let offerId = jQuery('.single_add_to_cart_button').val() || jQuery('input[name="product_id"]').val();

        if (offerId) {
            getCustomerInfo().then(function (customer) {
                if (!customer?.externalId) {
                    return;
                }

                setTimeout(() => {
                    ocapi.setCustomerSiteId(String(customer.externalId));
                    ocapi.event('page_view', { offer_external_id:  offerId })
                }, 1000)
            });
        }
    }

    function sendCartView()
    {
        if (jQuery(document.body).hasClass('woocommerce-cart')) {
            getCustomerInfo().then(function (customer) {
                if (!customer?.email || !customer?.externalId) {
                    return;
                }

                setTimeout(function() {
                    ocapi.setCustomerSiteId(String(customer.externalId));
                    ocapi.event('open_cart', { customer_email: customer.email});
                }, 1000);
            });
        }
    }

    function sendCartChange()
    {
        getCartItems().then(function(response) {
            if (!response?.cart_id || !Array.isArray(response.items) || response.items.length === 0) {
                return;
            }

            const cart = {
                cart_id: response.cart_id,
                items: response.items.map(function(item) {
                    return {
                        external_id: item.id,
                        xml_id: item.sku,
                        price: item.price,
                        quantity: item.quantity
                    };
                })
            };

            getCustomerInfo().then(function (customer) {
                if (!customer?.externalId) {
                    return;
                }

                setTimeout(function() {
                    ocapi.setCustomerSiteId(String(customer.externalId));
                    ocapi.event('cart', cart);
                }, 1000);
            });
        });
    }

    async function getCustomerInfo() {
        try {
            const response = await jQuery.ajax({
                url: RetailcrmTracker.url + 'admin-ajax.php?action=retailcrm_get_customer_info_for_tracker',
                method: 'POST',
                data: { ajax: 1 },
                dataType: 'json'
            })

            return response.success ? response.data : []
        } catch (error) {
            console.error('AJAX ошибка:', error);

            return [];
        }
    }

    async function getCartItems() {
        try {
            const response = await jQuery.ajax({
                url: RetailcrmTracker.url + 'admin-ajax.php?action=retailcrm_get_cart_items_for_tracker',
                method: 'POST',
                data: { ajax: 1 },
                dataType: 'json'
            })

            return response.success ? response.data : []
        } catch (error) {
            console.error('AJAX ошибка:', error);

            return [];
        }
    }
}
