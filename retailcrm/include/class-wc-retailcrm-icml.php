<?php
/**
 * Retailcrm Integration.
 *
 * @package  WC_Retailcrm_Icml
 * @category Integration
 * @author   Retailcrm
 */

if ( ! class_exists( 'WC_Retailcrm_Icml' ) ) :

    /**
     * Class WC_Retailcrm_Icml
     */
    class WC_Retailcrm_Icml
    {
        protected $shop;
        protected $file;
        protected $tmpFile;

        protected $properties = array(
            'name',
            'productName',
            'price',
            'purchasePrice',
            'vendor',
            'picture',
            'url',
            'xmlId',
            'productActivity'
        );

        protected $xml;

        /** @var SimpleXMLElement $categories */
        protected $categories;

        /** @var SimpleXMLElement $categories */
        protected $offers;

        protected $chunk = 500;
        protected $fileLifeTime = 3600;

        /**
         * WC_Retailcrm_Icml constructor.
         *
         */
        public function __construct()
        {
            $this->shop = get_bloginfo( 'name' );
            $this->file = ABSPATH . 'retailcrm.xml';
            $this->tmpFile = sprintf('%s.tmp', $this->file);
        }

        /**
         * Generate file
         */
        public function generate()
        {
            $categories = $this->get_wc_categories_taxonomies();
            $offers = $this->get_wc_products_taxonomies();

            if (file_exists($this->tmpFile)) {
                if (filectime($this->tmpFile) + $this->fileLifeTime < time()) {
                    unlink($this->tmpFile);
                    $this->writeHead();
                }
            } else {
                $this->writeHead();
            }

            try {
                if (!empty($categories)) {
                    $this->writeCategories($categories);
                    unset($categories);
                }

                if (!empty($offers)) {
                    $this->writeOffers($offers);
                    unset($offers);
                }

                $dom = dom_import_simplexml(simplexml_load_file($this->tmpFile))->ownerDocument;
                $dom->formatOutput = true;
                $formatted = $dom->saveXML();

                unset($dom, $this->xml);

                file_put_contents($this->tmpFile, $formatted);
                rename($this->tmpFile, $this->file);
            } catch (Exception $e) {
                unlink($this->tmpFile);
            }
        }

        /**
         * Load tmp data
         *
         * @return \SimpleXMLElement
         */
        private function loadXml()
        {
            return new SimpleXMLElement(
                $this->tmpFile,
                LIBXML_NOENT | LIBXML_NOCDATA | LIBXML_COMPACT | LIBXML_PARSEHUGE,
                true
            );
        }

        /**
         * Generate xml header
         */
        private function writeHead()
        {
            $string = sprintf(
                '<?xml version="1.0" encoding="UTF-8"?><yml_catalog date="%s"><shop><name>%s</name><categories/><offers/></shop></yml_catalog>',
                date('Y-m-d H:i:s'),
                $this->shop
            );

            file_put_contents($this->tmpFile, $string, LOCK_EX);
        }

        /**
         * Write categories in file
         *
         * @param $categories
         */
        private function writeCategories($categories)
        {
            $chunkCategories = array_chunk($categories, $this->chunk);
            foreach ($chunkCategories as $categories) {
                $this->xml = $this->loadXml();

                $this->categories = $this->xml->shop->categories;
                $this->addCategories($categories);

                $this->xml->asXML($this->tmpFile);
            }

            unset($this->categories);
        }

        /**
         * Write products in file
         *
         * @param $offers
         */
        private function writeOffers($offers)
        {
            $chunkOffers = array_chunk($offers, $this->chunk);
            foreach ($chunkOffers as $offers) {
                $this->xml = $this->loadXml();

                $this->offers = $this->xml->shop->offers;
                $this->addOffers($offers);

                $this->xml->asXML($this->tmpFile);
            }

            unset($this->offers);
        }

        /**
         * Add categories
         *
         * @param $categories
         */
        private function addCategories($categories)
        {
            $categories = self::filterRecursive($categories);

            foreach($categories as $category) {
                if (!array_key_exists('name', $category) || !array_key_exists('id', $category)) {
                    continue;
                }

                /** @var SimpleXMLElement $e */
                /** @var SimpleXMLElement $cat */

                $cat = $this->categories;
                $e = $cat->addChild('category', $category['name']);

                $e->addAttribute('id', $category['id']);

                if (array_key_exists('parentId', $category) && $category['parentId'] > 0) {
                    $e->addAttribute('parentId', $category['parentId']);
                }
            }
        }

        /**
         * Add offers
         *
         * @param $offers
         */
        private function addOffers($offers)
        {
            $offers = self::filterRecursive($offers);

            foreach ($offers as $key => $offer) {

                if (!array_key_exists('id', $offer)) {
                    continue;
                }

                $e = $this->offers->addChild('offer');

                $e->addAttribute('id', $offer['id']);

                if (!array_key_exists('productId', $offer) || empty($offer['productId'])) {
                    $offer['productId'] = $offer['id'];
                }
                $e->addAttribute('productId', $offer['productId']);

                if (!empty($offer['quantity'])) {
                    $e->addAttribute('quantity', (int) $offer['quantity']);
                } else {
                    $e->addAttribute('quantity', 0);
                }

                if (isset($offer['categoryId']) && $offer['categoryId']) {
                    if (is_array($offer['categoryId'])) {
                        foreach ($offer['categoryId'] as $categoryId) {
                            $e->addChild('categoryId', $categoryId);
                        }
                    } else {
                        $e->addChild('categoryId', $offer['categoryId']);
                    }
                }

                if (!array_key_exists('name', $offer) || empty($offer['name'])) {
                    $offer['name'] = 'Без названия';
                }

                if (!array_key_exists('productName', $offer) || empty($offer['productName'])) {
                    $offer['productName'] = $offer['name'];
                }

                unset($offer['id'], $offer['productId'], $offer['categoryId'], $offer['quantity']);
                array_walk($offer, array($this, 'setOffersProperties'), $e);

                if (array_key_exists('params', $offer) && !empty($offer['params'])) {
                    array_walk($offer['params'], array($this, 'setOffersParams'), $e);
                }

                unset($offers[$key]);
            }
        }

        /**
         * Set offer properties
         *
         * @param $value
         * @param $key
         * @param $e
         */
        private function setOffersProperties($value, $key, &$e) {
            if (in_array($key, $this->properties) && $key != 'params') {
                /** @var SimpleXMLElement $e */
                $e->addChild($key, htmlspecialchars($value));
            }
        }

        /**
         * Set offer params
         *
         * @param $value
         * @param $key
         * @param $e
         */
        private function setOffersParams($value, $key, &$e) {
            if (
                array_key_exists('code', $value) &&
                array_key_exists('name', $value) &&
                array_key_exists('value', $value) &&
                !empty($value['code']) &&
                !empty($value['name']) &&
                !empty($value['value'])
            ) {
                /** @var SimpleXMLElement $e */
                $param = $e->addChild('param', htmlspecialchars($value['value']));
                $param->addAttribute('code', $value['code']);
                $param->addAttribute('name', substr(htmlspecialchars($value['name']), 0, 200));
                unset($key);
            }
        }

        /**
         * Filter result array
         *
         * @param $haystack
         *
         * @return mixed
         */
        public static function filterRecursive($haystack)
        {
            foreach ($haystack as $key => $value) {
                if (is_array($value)) {
                    $haystack[$key] = self::filterRecursive($haystack[$key]);
                }

                if (is_null($haystack[$key]) || $haystack[$key] === '' || count($haystack[$key]) == 0) {
                    unset($haystack[$key]);
                } elseif (!is_array($value)) {
                    $haystack[$key] = trim($value);
                }
            }

            return $haystack;
        }

        /**
         * Get WC products
         *
         * @return array
         */
        private function get_wc_products_taxonomies() {
            $full_product_list = array();
            $loop = new WP_Query(array('post_type' => array('product', 'product_variation'), 'posts_per_page' => -1));

            while ($loop->have_posts()) : $loop->the_post();
                $theid = get_the_ID();
                if ( version_compare( get_option( 'woocommerce_db_version' ), '3.0', '<' ) ) {
                    $product = new WC_Product($theid);
                    $parent = new WC_Product($product->get_parent());
                } 
                else {
                    if (get_post_type($theid) == 'product') {
                        $product = new WC_Product_Simple($theid);
                    } 
                    elseif (get_post_type($theid) == 'product_variation') {
                        $post = get_post($theid);
                        
                        if (get_post($post->post_parent)) {
                            $product = new WC_Product_Variation($theid);
                            $parent = new WC_Product_Simple($product->get_parent_id());
                        } 
                    }
                }

                if ($this->get_parent_product($product) > 0) {
                    $image = wp_get_attachment_image_src( get_post_thumbnail_id( $parent->get_id() ), 'single-post-thumbnail' );
                    $term_list = wp_get_post_terms($parent->get_id(), 'product_cat', array('fields' => 'ids'));
                    $attributes = get_post_meta( $parent->get_id() , '_product_attributes' );
                } else {
                    $image = wp_get_attachment_image_src( get_post_thumbnail_id( $theid ), 'single-post-thumbnail' );
                    $term_list = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'ids'));
                    $attributes = get_post_meta( $product->get_id() , '_product_attributes' );
                }

                $attributes = (isset($attributes[0])) ? $attributes[0] : $attributes;

                $params = array();

                $weight = $product->get_weight();

                if (!empty($weight)) {
                    $params[] = array('code' => 'weight', 'name' => 'Weight', 'value' => $weight);
                }

                if (!empty($attributes)) {
                    foreach ($attributes as $attribute_name => $attribute) {
                        $attributeValue = get_post_meta($product->get_id(), 'attribute_'.$attribute_name);
                        $attributeValue = end($attributeValue);
                        if ($attribute['is_visible'] == 1 && !empty($attribute['value'])) {
                            $params[] = array(
                                'code' => $attribute_name, 
                                'name' => $attribute['name'], 
                                'value' => $attributeValue
                            );
                        }
                    }
                }

                if ($product->get_sku() != '') {
                    $params[] = array('code' => 'sku', 'name' => 'SKU', 'value' => $product->get_sku());
                }

                $product_data = array(
                    'id' => $product->get_id(),
                    'productId' => ($this->get_parent_product($product) > 0) ? $parent->get_id() : $product->get_id(),
                    'name' => $product->get_title(),
                    'productName' => ($this->get_parent_product($product) > 0) ? $parent->get_title() : $product->get_title(),
                    'price' => $this->get_price_with_tax($product),
                    'purchasePrice' => $product->get_regular_price(),
                    'picture' => $image[0],
                    'url' => ($this->get_parent_product($product) > 0) ? $parent->get_permalink() : $product->get_permalink(),
                    'quantity' => is_null($product->get_stock_quantity()) ? 0 : $product->get_stock_quantity(),
                    'categoryId' => $term_list
                );

                if (!empty($params)) {
                    $product_data['params'] = $params;
                }

                $full_product_list[] = $product_data;
                unset($product_data);
            endwhile;

            return $full_product_list;
        }

        /**
         * Get WC categories
         *
         * @return array
         */
        private function get_wc_categories_taxonomies() {
            $categories = array();
            $taxonomy     = 'product_cat';
            $orderby      = 'parent';
            $show_count   = 0;      // 1 for yes, 0 for no
            $pad_counts   = 0;      // 1 for yes, 0 for no
            $hierarchical = 1;      // 1 for yes, 0 for no
            $title        = '';
            $empty        = 0;

            $args = array(
                'taxonomy'     => $taxonomy,
                'orderby'      => $orderby,
                'show_count'   => $show_count,
                'pad_counts'   => $pad_counts,
                'hierarchical' => $hierarchical,
                'title_li'     => $title,
                'hide_empty'   => $empty
            );

            $wcatTerms = get_categories( $args );

            foreach ($wcatTerms as $term) {
                $categories[] = array(
                    'id' => $term->term_id,
                    'parentId' => $term->parent,
                    'name' => $term->name
                );
            }

            return $categories;
        }

        private function get_parent_product($product) {
            global $woocommerce;
            if ( version_compare( $woocommerce->version, '3.0', '<' ) ) {
                return $product->get_parent();
            } else {
                return $product->get_parent_id();
            }
        }

        private function get_price_with_tax($product) {
            global $woocommerce;
            if ( version_compare( $woocommerce->version, '3.0', '<' ) ) {
                return $product->get_price_including_tax();
            } else {
                return wc_get_price_including_tax($product);
            }
        }
    }

endif;
