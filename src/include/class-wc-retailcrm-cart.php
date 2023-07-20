<?php

if (!class_exists('WC_Retailcrm_Carts')) :
    /**
     * PHP version 7.0
     *
     * Class WC_Retailcrm_Cart - Allows transfer data carts with CMS.
     *
     * @category Integration
     * @author   RetailCRM <integration@retailcrm.ru>
     * @license  http://retailcrm.ru Proprietary
     * @link     http://retailcrm.ru
     * @see      http://help.retailcrm.ru
     */
    class WC_Retailcrm_Cart
    {
        protected $apiClient;
        protected $dateFormat;

        public function __construct($apiClient)
        {
            $this->apiClient = $apiClient;
            $this->dateFormat = 'Y-m-d H:i:sP';
        }

        public function isCartExist($customerId, $site): bool
        {
            $getCart = $this->apiClient->cartGet($customerId, $site);

            return !empty($getCart['cart']['externalId']);
        }

        public function processCart($customerId, $cartItems, $site, $isCartExist): bool
        {
            $isSuccessful = false;

            try {
                $crmCart = [
                    'customer' => ['externalId' => $customerId],
                    'clearAt' => null,
                    'updatedAt' => date($this->dateFormat),
                    'droppedAt' => date($this->dateFormat)
                ];

                // If new cart, need set createdAt and externalId
                if (!$isCartExist) {
                    $crmCart['createdAt'] = date($this->dateFormat);
                    $crmCart['externalId'] = $customerId . uniqid('_', true);
                }

                // If you delete one by one
                if (empty($cartItems)) {
                    return $this->clearCart($customerId, $site, $isCartExist);
                }

                foreach ($cartItems as $item) {
                    $product = $item['data'];

                    $crmCart['items'][] = [
                        'offer' => ['externalId' => $product->get_id()],
                        'quantity' => $item['quantity'],
                        'createdAt' => $product->get_date_created()->date($this->dateFormat) ?? date($this->dateFormat),
                        'updatedAt' => $product->get_date_modified()->date($this->dateFormat) ?? date($this->dateFormat),
                        'price' => wc_get_price_including_tax($product),
                    ];
                }

                $crmCart = apply_filters(
                    'retailcrm_process_cart',
                    WC_Retailcrm_Plugin::clearArray($crmCart),
                    $cartItems
                );

                $setResponse = $this->apiClient->cartSet($crmCart, $site);
                $isSuccessful = $setResponse->isSuccessful() && !empty($setResponse['success']);
            } catch (Throwable $exception) {
                writeBaseLogs('Error process cart: ' . $exception->getMessage());
            }

            return $isSuccessful;
        }

        public function clearCart($customerId, $site, $isCartExist): bool
        {
            $isSuccessful = false;

            try {
                if ($isCartExist) {
                    $crmCart = ['customer' => ['externalId' => $customerId], 'clearedAt' => date($this->dateFormat)];
                    $clearResponse = $this->apiClient->cartClear($crmCart, $site);
                    $isSuccessful = $clearResponse->isSuccessful() && !empty($clearResponse['success']);
                }
            } catch (Throwable $exception) {
                writeBaseLogs('Error clear cart: ' . $exception->getMessage());
            }

            return $isSuccessful;
        }
    }
endif;
