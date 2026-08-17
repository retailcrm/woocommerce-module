<?php

if (!class_exists('WC_Retailcrm_Shipping_Stock')) :
    /**
     * Filters shipping rates by the stock available in mapped CRM stores.
     */
    class WC_Retailcrm_Shipping_Stock
    {
        /** @var array */
        private $mapping;

        public function __construct(array $mapping = [])
        {
            $this->mapping = $mapping;
        }

        /**
         * @param array $rates
         * @param array $package
         *
         * @return array
         */
        public function filter_rates($rates, $package)
        {
            if (empty($this->mapping) || empty($package['contents'])) {
                return $rates;
            }

            $products = [];

            foreach ($package['contents'] as $item) {
                if (empty($item['data']) || !is_a($item['data'], 'WC_Product')) {
                    continue;
                }

                $productId = $item['data']->get_id();

                if (!isset($products[$productId])) {
                    $products[$productId] = [
                        'product' => $item['data'],
                        'quantity' => 0,
                    ];
                }

                $products[$productId]['quantity'] += $item['quantity'];
            }

            foreach ($rates as $rateId => $rate) {
                $storeCode = $this->mapping[$rateId] ?? '';

                if ($storeCode === '') {
                    continue;
                }

                foreach ($products as $item) {
                    $storeStocks = $item['product']->get_meta(WC_Retailcrm_Inventories::STORE_STOCKS_META_KEY, true);

                    // No meta means that this product has not been synchronized yet.
                    if (!is_array($storeStocks)) {
                        continue;
                    }

                    if (($storeStocks[$storeCode] ?? 0) < $item['quantity']) {
                        unset($rates[$rateId]);
                        break;
                    }
                }
            }

            return $rates;
        }
    }
endif;
