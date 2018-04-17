<?php
/**
 * RetailCRM Integration.
 *
 * @package  WC_Retailcrm_Inventories
 * @category Integration
 * @author   RetailCRM
 */

if ( ! class_exists( 'WC_Retailcrm_Inventories' ) ) :

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

        public function load_stocks()
        {
            if (!$this->retailcrm) {
                return;
            }

            $page = 1;

            do {
                $result = $this->retailcrm->storeInventories(array(), $page, 250);
                $totalPageCount = $result['pagination']['totalPageCount'];
                $page++;

                foreach ($result['offers'] as $offer) {
                    if (isset($offer['externalId'])) {
                        $product = wc_get_product($offer['externalId']);
                        
                        if ($product != false) {
                            if ($product->get_type() == 'variable') {
                                continue;
                            }
                            update_post_meta($offer['externalId'], '_manage_stock', 'yes');
                            $product->set_stock_quantity($offer['quantity']);
                            $product->save();
                        }
                    }
                }
            } while ($page <= $totalPageCount);
        }

        public function updateQuantity()
        {
            $options = array_filter(get_option(WC_Retailcrm_Base::$option_key));

            if ($options['sync'] == 'yes') {
                $this->load_stocks();
            } else {
                return false;
            }
        }
    }
endif;
