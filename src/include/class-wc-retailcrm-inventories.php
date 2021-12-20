<?php
/**
 * RetailCRM Integration.
 *
 * @package  WC_Retailcrm_Inventories
 * @category Integration
 * @author   RetailCRM
 */

if (!class_exists('WC_Retailcrm_Inventories')) :

    /**
     * Class WC_Retailcrm_Inventories
     */
    class WC_Retailcrm_Inventories
    {
        /** @var WC_Retailcrm_Client_V5 */
        protected $retailcrm;

        /** @var array  */
        protected $retailcrm_settings;

        /** @var string */
        protected $bind_field = 'externalId';

        /**
         * WC_Retailcrm_Inventories constructor.
         * @param bool $retailcrm
         */
        public function __construct($retailcrm = false)
        {
            $this->retailcrm_settings = get_option(WC_Retailcrm_Base::$option_key);
            $this->retailcrm = $retailcrm;

            if (isset($this->retailcrm_settings['bind_by_sku'])
                && $this->retailcrm_settings['bind_by_sku'] == WC_Retailcrm_Base::YES
            ) {
                $this->bind_field = 'xmlId';
            }
        }

        /**
         * Load stock from retailCRM
         *
         * @return mixed
         */
        protected function load_stocks()
        {
            $success = array();

            if (!$this->retailcrm instanceof WC_Retailcrm_Proxy) {
                return null;
            }

            $page = 1;
            $variationProducts = array();

            do {
                /** @var WC_Retailcrm_Response $response */
                $response = $this->retailcrm->storeInventories(array(), $page, 250);

                if (empty($response) || !$response->isSuccessful()) {
                    return null;
                }

                $totalPageCount = $response['pagination']['totalPageCount'];
                $page++;

                foreach ($response['offers'] as $offer) {
                    if (isset($offer[$this->bind_field])) {
                        $product = retailcrm_get_wc_product($offer[$this->bind_field], $this->retailcrm_settings);

                        if ($product instanceof WC_Product) {
                            if ($product->get_type() == 'variation' || $product->get_type() == 'variable') {
                                $parentId = $product->get_parent_id();

                                if (!empty($parentId)) {
                                    if (isset($variationProducts[$parentId])) {
                                        $variationProducts[$parentId] += $offer['quantity'];
                                    } else {
                                        $variationProducts[$parentId] = $offer['quantity'];
                                    }
                                }
                            }

                            $product->set_manage_stock(true);
                            $product->set_stock_quantity($offer['quantity']);
                            $success[] = $product->save();
                        }
                    }
                }

                if (!empty($variationProducts)) {
                    foreach ($variationProducts as $id => $quantity) {
                        $variationProduct = wc_get_product($id);

                        if (is_object($variationProduct)) {
                            $variationProduct->set_manage_stock(true);
                            $variationProduct->set_stock_quantity($quantity);
                            $success[] = $variationProduct->save();
                        }
                    }
                }
            } while ($page <= $totalPageCount);

            return $success;
        }

        /**
         * Update stock quantity in WooCommerce
         *
         * @return mixed
         */
        public function updateQuantity()
        {
            if ($this->retailcrm_settings['sync'] == WC_Retailcrm_Base::YES) {
                return $this->load_stocks();
            }

            return false;
        }
    }
endif;

