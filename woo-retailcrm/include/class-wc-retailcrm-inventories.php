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
        public function __construct()
        {
            $this->retailcrm_settings = get_option( 'woocommerce_integration-retailcrm_settings' );

            if ( ! class_exists( 'WC_Retailcrm_Proxy' ) ) {
                include_once( __DIR__ . '/api/class-wc-retailcrm-proxy.php' );
            }

            $this->retailcrm = new WC_Retailcrm_Proxy(
                $this->retailcrm_settings['api_url'],
                $this->retailcrm_settings['api_key'],
                $this->retailcrm_settings['api_version']
            );
        }

        public function load_stocks()
        {    
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
            $options = array_filter(get_option( 'woocommerce_integration-retailcrm_settings' ));
            
            if ($options['sync'] == 'yes') {
                $this->load_stocks();
            } else {
                return false;
            }
        }
    }
endif;
