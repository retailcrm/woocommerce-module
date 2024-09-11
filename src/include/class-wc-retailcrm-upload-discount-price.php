<?php

if (!class_exists('WC_Retailcrm_Upload_Discount_Price')):

    class WC_Retailcrm_Upload_Discount_Price
    {
        const DISCOUNT_TYPE_PRICE = 'woo-promotion-lp';

        protected $activeLoyalty = false;

        protected $settings;

        protected $site;

        /** @var bool|WC_Retailcrm_Proxy|WC_Retailcrm_Client_V5 */
        protected $apiClient;

        public function __construct($aplClient = false)
        {
            $this->settings = get_option(WC_Retailcrm_Base::$option_key);
            $this->apiClient = $aplClient;

            if (isset($this->settings['loyalty']) && $this->settings['loyalty'] === WC_Retailcrm_Base::YES) {
                $this->activeLoyalty = true;
            }
        }

        public function upload()
        {
            if (!$this->activeLoyalty) {
                return;
            }

            $error = $this->uploadSettings();

            if ($error !== '') {
                writeBaseLogs($error);

                return;
            }

            $productStatuses = $this->getProductStatuses();

            if (!$productStatuses) {
                $productStatuses = ['publish'];
            }

            $page = 1;
            $requestData = [];

            do {
                $products = wc_get_products(
                    [
                        'limit' => 1000,
                        'status' => $productStatuses,
                        'page' => $page,
                        'paginate' => true
                    ]
                );

                wp_cache_flush_runtime();

                if (empty($products)) {
                    writeBaseLogs('Can`t get products!');

                    return;
                }

                try {
                    foreach ($products->products as $offer) {
                        $type = $offer->get_type();

                        if (strpos($type, 'variable') !== false || strpos($type, 'variation') !== false) {
                            foreach ($offer->get_children() as $childId) {
                                $childProduct = wc_get_product($childId);

                                if (!$childProduct) {
                                    continue;
                                }

                                $sendOffer = $this->getOfferData($childProduct);

                                if ($sendOffer !== []) {
                                    $requestData[] = $sendOffer;
                                }
                            }
                        } else {
                            $sendOffer = $this->getOfferData($offer);

                            if ($sendOffer !== []) {
                                $requestData[] = $sendOffer;
                            }
                        }
                    }

                    $chunks = array_chunk($requestData, 250);

                    foreach ($chunks as $chunk) {
                        $this->apiClient->storePricesUpload($chunk, $this->site);
                        time_nanosleep(0, 200000000);
                    }

                    unset($chunks);
                } catch (\Throwable $exception) {
                    writeBaseLogs($exception->getMessage() . PHP_EOL . $exception->getTraceAsString());

                    return;
                }

                ++$page;
            } while ($page <= $products->max_num_pages);
        }

        private function getOfferData(WC_Product $product)
        {
            $currentPrice = wc_get_price_including_tax($product);
            $defaultPrice = wc_get_price_including_tax($product, ["price" => $product->get_regular_price()]);

            if ($currentPrice === $defaultPrice) {
                return [];
            }

            return [
                'externalId' => $product->get_id(),
                'site' => $this->site,
                'prices' => [
                    [
                        'code' => self::DISCOUNT_TYPE_PRICE,
                        'price' => $currentPrice
                    ]
                ]
            ];
        }

        private function getProductStatuses()
        {
            $statuses = [];

            foreach (get_post_statuses() as $key => $value) {
                if (isset($this->settings['p_' . $key]) && $this->settings['p_' . $key] == WC_Retailcrm_Base::YES) {
                    $statuses[] = $key;
                }
            }

            return $statuses;
        }

        private function uploadSettings()
        {
            if (!$this->apiClient instanceof WC_Retailcrm_Proxy) {
               return 'API client has not been initialized';
            }

            $this->site = $this->apiClient->getSingleSiteForKey();

            if (empty($this->site)) {
                return 'Error with CRM credentials: need an valid apiKey assigned to one certain site';
            }

            $response = $this->apiClient->getPriceTypes();

            if (
                !$response instanceof WC_Retailcrm_Response
                || !$response->offsetExists('priceTypes')
                || empty($response['priceTypes'])
            ) {
                return 'Error getting price types';
            }

            $defaultPrice = null;
            $discountPriceType = null;

            foreach ($response['priceTypes'] as $priceType) {
                if ($priceType['default'] === true) {
                    $defaultPrice = $priceType;
                }

                if ($priceType['code'] === self::DISCOUNT_TYPE_PRICE) {
                    $discountPriceType = $priceType;
                }
            }

            if ($discountPriceType === null) {
                $discountPriceType = [
                    'code' => self::DISCOUNT_TYPE_PRICE,
                    'name' => __('Woocommerce promotional price', 'retailcrm'),
                    'active' => true,
                    'description' => __('Promotional price type for Woocommerce store, generated automatically.
                     Necessary for correct synchronization work when loyalty program is enabled
                      (Do not delete. Do not deactivate)', 'retailcrm'),
                    'ordering' => 999
                ];

                if (isset($defaultPrice['geo'])) {
                    $discountPriceType['geo'] = $defaultPrice['geo'];
                }

                if (isset($defaultPrice['groups'])) {
                    $discountPriceType['groups'] = $defaultPrice['groups'];
                }

                if (isset($defaultPrice['currency'])) {
                    $discountPriceType['currency'] = $defaultPrice['currency'];
                }

                if (isset($defaultPrice['filterExpression'])) {
                    $discountPriceType['filterExpression'] = $defaultPrice['filterExpression'];
                }

                $response = $this->apiClient->editPriceType($discountPriceType);

                if (!$response instanceof WC_Retailcrm_Response || !$response['success']) {
                    return 'Error creating price type';
                }
            } elseif ($discountPriceType['active'] === false) {
                $discountPriceType['active'] = true;

                $response = $this->apiClient->editPriceType($discountPriceType);

                if (!$response instanceof WC_Retailcrm_Response || !$response['success']) {
                    return 'Error activate price type';
                }
            }

            return '';
        }
    }

endif;
