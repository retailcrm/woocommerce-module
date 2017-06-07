<?php
/**
 * Retailcrm Integration.
 *
 * @package  WC_Retailcrm_Inventories
 * @category Integration
 * @author   Retailcrm
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
                $this->retailcrm_settings['api_key']
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
                        $post = get_post($offer['externalId']);
                         
                        if ($post->post_type == 'product') {
                            $product = new WC_Product_Simple($offer['externalId']);
                            update_post_meta($offer['externalId'], '_manage_stock', 'yes');
                            $product->set_stock($offer['quantity']);
                        } elseif ($post->post_type == 'product_variation') {
                            $args = array();
                            if ($post->post_parent) {
                                $args['parent_id'] = $post->post_parent;
                                $args['parent'] = new WC_Product_Simple($post->post_parent);
                            }
                            $product = new WC_Product_Variation($offer['externalId'], $args);
                            update_post_meta($offer['externalId'], '_manage_stock', 'yes');
                            $product->set_stock($offer['quantity']);
                        }
                    }
                }

            } while ($page < $totalPageCount);
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
