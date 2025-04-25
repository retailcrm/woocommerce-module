jQuery(function() {
    function startTracker(...trackers)
    {
        if (trackers.includes('page_view')) {
            sendProductView();
        }

        if (trackers.includes('open_cart')) {
            sendCartView()
        }

        if (trackers.includes('cart')) {
            sendCartChange()
        }
    }

    // Проверил, все корректно передается.
    function sendProductView()
    {
        let offerId = jQuery('.single_add_to_cart_button').val() || jQuery('input[name="product_id"]').val();

        if (offerId) {
            setTimeout(() => {
                ocapi.event('page_view', { offer_external_id:  offerId })
            }, 3000)
        }
    }

    // Реализовать получение email клиента. А лучше все данные клиента
    function sendCartView()
    {
        if (jQuery(document.body).hasClass('woocommerce-cart')) {
            setTimeout(() => {
                ocapi.event('open_cart')
            }, 5000)
        }
    }

    function sendCartChange()
    {
        let cartObject = {};
        cartObject.items = [];

        // Добавление товара в корзину
        jQuery(document.body).on('adding_to_cart', function (event, button, data) {
            console.log('Товар добавлен в корзину 🛒');
            console.log('Данные:', data);
        });

        // Обновление состава корзины, срабатывает как на удаление товара так и на изменение кол-во товаров на странице корзины
        jQuery(document.body).on('wc_fragments_refreshed', function () {
            getCartItems().then(function(cartItems) {
                console.log(cartItems);
            });
        });

        // Товар добавлен через карточку товара, после нажатия на кнопку происходит перезагрузка страницы
        jQuery(document.body).on('click', '.single_add_to_cart_button', function (event)  {
            sessionStorage.setItem('click_single__add_to_cart_button', '1');
        });

        if (sessionStorage.getItem('click_single__add_to_cart_button') === '1') {
            sessionStorage.removeItem('click_single__add_to_cart_button');

            getCartItems().then(function(cartItems) {
                console.log(cartItems);
            });
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
});
