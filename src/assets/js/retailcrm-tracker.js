let cartListenersInitialized = false;

function startTracker(...trackers)
{
    if (trackers.includes('page_view')) {
        sendProductView();
    }

    if (trackers.includes('open_cart')) {
        sendCartView()
    }

    if (trackers.includes('cart')) {
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

    // Проверил, все корректно передается.
    function sendProductView()
    {
        let offerId = jQuery('.single_add_to_cart_button').val() || jQuery('input[name="product_id"]').val();

        if (offerId) {
            setTimeout(() => {
                ocapi.event('page_view', { offer_external_id:  offerId })
            }, 1000)
        }
    }

    // Реализовать получение email клиента. А лучше все данные клиента
    function sendCartView()
    {
        if (jQuery(document.body).hasClass('woocommerce-cart')) {
            setTimeout(() => {
                ocapi.event('open_cart')
            }, 1000)
        }
    }

    function sendCartChange()
    {
        let cart = {};
        cart.items = [];

        getCartItems().then(function(cartItems) {
            cartItems.forEach(item => {
                cart.items.push({
                    external_id: item.id,
                    price: item.price,
                    quantity: item.quantity
                });
            });
        });

        console.log(cart);

        if (cart.items !== []) {
            setTimeout(function() {
                ocapi.event('cart', cart);
            }, 1500);
        }
    }

    async function getCartItems() {
        try {
            const response = await jQuery.ajax({
                url: AdminUrl.url + '/admin-ajax.php?action=get_cart_items_for_tracker',
                method: "POST",
                data: { ajax: 1 },
                dataType: "json"
            })

            return response.success ? response.data : []
        } catch (error) {
            console.error('AJAX ошибка:', error);

            return [];
        }
    }
}

