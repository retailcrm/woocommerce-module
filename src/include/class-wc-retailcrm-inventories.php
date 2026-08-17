<?php

if (!class_exists('WC_Retailcrm_Inventories')) :
    /**
     * PHP version 7.0
     *
     * Class WC_Retailcrm_Inventories - Allows manage stocks.
     *
     * @category Integration
     * @author   RetailCRM <integration@retailcrm.ru>
     * @license  http://retailcrm.ru Proprietary
     * @link     http://retailcrm.ru
     * @see      http://help.retailcrm.ru
     */
    class WC_Retailcrm_Inventories
    {
        const STORE_STOCKS_META_KEY = '_retailcrm_store_stocks';

        /** @var WC_Retailcrm_Client_V5 */
        protected $retailcrm;

        /** @var array  */
        protected $crmSettings;

        /** @var string */
        protected $bindField = 'externalId';

        /**
         * WC_Retailcrm_Inventories constructor.
         * @param bool $retailcrm
         */
        public function __construct($retailcrm = false)
        {
            $this->crmSettings = get_option(WC_Retailcrm_Base::$option_key);
            $this->retailcrm = $retailcrm;

            if (!empty($this->crmSettings['bind_by_sku']) && $this->crmSettings['bind_by_sku'] === WC_Retailcrm_Base::YES) {
                $this->bindField = 'xmlId';
            }
        }

        /**
         * Load stock from RetailCRM
         *
         * @return void
         */
        protected function load_stocks()
        {
            if (!$this->retailcrm instanceof WC_Retailcrm_Proxy) {
                return null;
            }

            $page = 1;
            $availableStores = $this->crmSettings['stores_for_uploading'] ?? null;
            $mappedStores = array_values(array_unique(array_filter(
                $this->crmSettings['shipping_store_mapping'] ?? []
            )));
            $variationProducts = [];
            $stocksUpdated = false;

            do {
                /** @var WC_Retailcrm_Response $response */
                $response = $this->retailcrm->storeInventories(['details' => true], $page, 250);

                if (empty($response['offers']) || !$response->isSuccessful()) {
                    break;
                }

                $totalPageCount = $response['pagination']['totalPageCount'];
                $page++;

                foreach ($response['offers'] as $offer) {
                    $offerQuantity = $offer['quantity'];
                    $storeQuantities = array_fill_keys($mappedStores, 0);

                    foreach ($offer['stores'] ?? [] as $store) {
                        if (array_key_exists($store['store'], $storeQuantities)) {
                            $storeQuantities[$store['store']] = $store['quantity'];
                        }
                    }

                    if (!empty($availableStores) && count($offer['stores']) > 1) {
                        $offerQuantity = 0;

                        foreach ($offer['stores'] as $store) {
                            if (in_array($store['store'], $availableStores, true)) {
                                $offerQuantity += $store['quantity'];
                            }
                        }
                    }

                    if (isset($offer[$this->bindField])) {
                        $product = retailcrm_get_wc_product($offer[$this->bindField], $this->crmSettings);

                        if ($product instanceof WC_Product) {
                            if ($product->get_type() === 'external') {
                                continue;
                            }

                            if ($product->get_type() == 'variation' || $product->get_type() == 'variable') {
                                $parentId = $product->get_parent_id();

                                if (!empty($parentId)) {
                                    if (!isset($variationProducts[$parentId])) {
                                        $variationProducts[$parentId] = [
                                            'quantity' => 0,
                                            'stores' => array_fill_keys($mappedStores, 0),
                                        ];
                                    }

                                    $variationProducts[$parentId]['quantity'] += $offerQuantity;

                                    foreach ($storeQuantities as $storeCode => $quantity) {
                                        $variationProducts[$parentId]['stores'][$storeCode] += $quantity;
                                    }
                                }
                            }

                            $product->set_manage_stock(true);
                            $product->set_stock_quantity($offerQuantity);

                            if (!empty($mappedStores)) {
                                $product->update_meta_data(self::STORE_STOCKS_META_KEY, $storeQuantities);
                            } else {
                                $product->delete_meta_data(self::STORE_STOCKS_META_KEY);
                            }

                            $product->save();
                            $stocksUpdated = true;
                        }
                    }
                }

                // Clearing the object cache after calling the function wc_get_products
                wp_cache_flush();
            } while ($page <= $totalPageCount);

            if (!empty($variationProducts)) {
                $chunks = array_chunk($variationProducts, 100, true);

                foreach ($chunks as $chunk) {
                    foreach ($chunk as $id => $stockData) {
                        $variationProduct = wc_get_product($id);

                        if (is_object($variationProduct)) {
                            $variationProduct->set_manage_stock(true);
                            $variationProduct->set_stock_quantity($stockData['quantity']);

                            if (!empty($mappedStores)) {
                                $variationProduct->update_meta_data(
                                    self::STORE_STOCKS_META_KEY,
                                    $stockData['stores']
                                );
                            } else {
                                $variationProduct->delete_meta_data(self::STORE_STOCKS_META_KEY);
                            }

                            $variationProduct->save();
                            $stocksUpdated = true;
                        }
                    }

                    wp_cache_flush();
                }
            }

            if ($stocksUpdated) {
                WC_Cache_Helper::get_transient_version('shipping', true);
            }
        }

        /**
         * Update stock quantity in WooCommerce
         *
         * @return void
         */
        public function updateQuantity()
        {
            if ($this->crmSettings['sync'] === WC_Retailcrm_Base::YES) {
                $this->load_stocks();
            }
        }
    }
endif;
