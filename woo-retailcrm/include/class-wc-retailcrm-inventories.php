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
        protected $retailcrm;
        protected $retailcrm_settings;

        /**
         * WC_Retailcrm_Inventories constructor.
         * @param bool $retailcrm
         */
        public function __construct($retailcrm = false)
        {
            $this->retailcrm_settings = get_option(WC_Retailcrm_Base::$option_key);
            $this->retailcrm = $retailcrm;
        }

        /**
         * Load stock from retailCRM
         *
         * @return mixed
         */
        public function load_stocks()
        {
            $success = array();

            if (!$this->retailcrm) {
                return;
            }

            $page = 1;

            do {
                $result = $this->retailcrm->storeInventories(array(), $page, 250);

                if (!$result->isSuccessful()) {
                    return;
                }

                $totalPageCount = $result['pagination']['totalPageCount'];
                $page++;

                foreach ($result['offers'] as $offer) {
                    if (isset($offer['externalId'])) {
                        $product = wc_get_product($offer['externalId']);

                        if ($product instanceof WC_Product) {
                            if ($product->get_type() == 'variable') {
                                continue;
                            }

                            $product->set_manage_stock(true);
                            $product->set_stock_quantity($offer['quantity']);
                            $success[] = $product->save();
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
            if ($this->retailcrm_settings['sync'] == 'yes') {
                return $this->load_stocks();
            }

            return false;
        }
    }
endif;
