<?php
/**
 * RetailCRM Integration.
 *
 * @package  WC_Retailcrm_Icml
 * @category Integration
 * @author   RetailCRM
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

        /** @var array */
        protected $settings;

        /**
         * WC_Retailcrm_Icml constructor.
         *
         */
        public function __construct()
        {
            $this->settings = get_option(WC_Retailcrm_Base::$option_key);
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

                $status_args = $this->checkPostStatuses();
                $this->get_wc_products_taxonomies($status_args);
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
                current_time('Y-m-d H:i:s'),
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
                $e = $cat->addChild('category');

                $e->addAttribute('id', $category['id']);

                if (array_key_exists('parentId', $category) && $category['parentId'] > 0) {
                    $e->addAttribute('parentId', $category['parentId']);
                }

                $e->addChild('name', $category['name']);

                if (array_key_exists('picture', $category)) {
                    $e->addChild('picture', $category['picture']);
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

                if (array_key_exists('dimensions', $offer)) {
                    $e->addChild('dimensions', $offer['dimensions']);
                }

                if (array_key_exists('weight', $offer)) {
                    $e->addChild('weight', $offer['weight']);
                }

                if (array_key_exists('tax', $offer)) {
                    $e->addChild('vatRate', $offer['tax']);
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

                if (is_null($haystack[$key])
                    || $haystack[$key] === ''
                    || (is_array($haystack[$key]) && count($haystack[$key]) == 0)
                ) {
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
         * @return void
         */
        private function get_wc_products_taxonomies($status_args) {
            if (!$status_args) {
                $status_args = array('publish');
            }

            $attribute_taxonomies = wc_get_attribute_taxonomies();
            $product_attributes = array();

            foreach ($attribute_taxonomies as $product_attribute) {
                $attribute_id = wc_attribute_taxonomy_name_by_id(intval($product_attribute->attribute_id));
                $product_attributes[$attribute_id] = $product_attribute->attribute_label;
            }

            $full_product_list = array();

            $products = wc_get_products(
                array(
                    'limit' => -1,
                    'status' => $status_args
                )
            );

            foreach ($products as $offer) {
                if ($offer->get_type() == 'simple') {
                    $this->setOffer($full_product_list, $product_attributes, $offer);
                } elseif ($offer->get_type() == 'variable') {
                    foreach ($offer->get_children() as $child_id) {
                        $child_product = wc_get_product($child_id);
                        if (!$child_product) {
                            continue;
                        }

                        $this->setOffer($full_product_list, $product_attributes, $child_product, $offer);
                    }
                }
            }

            if (isset($full_product_list) && $full_product_list) {
                $this->writeOffers($full_product_list);
                unset($full_product_list);
            }
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

            $wcatTerms = get_categories($args);

            foreach ($wcatTerms as $term) {
                $category = array(
                    'id' => $term->term_id,
                    'parentId' => $term->parent,
                    'name' => $term->name
                );

                $thumbnail_id = function_exists('get_term_meta')
                    ? get_term_meta($term->term_id, 'thumbnail_id', true)
                    : get_woocommerce_term_meta($term->term_id, 'thumbnail_id', true);
                $picture = wp_get_attachment_url($thumbnail_id);

                if ($picture) {
                    $category['picture'] = $picture;
                }

                $categories[] = $category;
            }

            return $categories;
        }

        /**
         * Set offer for icml catalog
         *
         * @param array $full_product_list
         * @param array $product_attributes
         * @param WC_Product $product
         * @param bool | WC_Product_Variable $parent
         *
         * @return void
         */
        private function setOffer(&$full_product_list, $product_attributes, $product, $parent = false) {
            if ($parent) {
                $image = wp_get_attachment_image_src($product->get_image_id(), 'full');

                if (!$image) {
                    $image = wp_get_attachment_image_src($parent->get_image_id(), 'full');
                }

                $term_list = $parent->get_category_ids();
                $attributes = get_post_meta($parent->get_id(), '_product_attributes');
            } else {
                $image = wp_get_attachment_image_src($product->get_image_id(), 'full');
                $term_list = $product->get_category_ids();
                $attributes = get_post_meta($product->get_id(), '_product_attributes');
            }

            $attributes = (isset($attributes[0])) ? $attributes[0] : $attributes;

            $params = array();

            if (!empty($attributes)) {
                foreach ($attributes as $attribute_name => $attribute) {
                    $attributeValue = $product->get_attribute($attribute_name);
                    if ($attribute['is_visible'] == 1 && !empty($attributeValue)) {
                        $params[] = array(
                            'code' => $attribute_name,
                            'name' => $product_attributes[$attribute_name],
                            'value' => $attributeValue
                        );
                    }
                }
            }

            $dimensions = '';

            if ($product->get_length() != '') {
                $dimensions = wc_get_dimension($product->get_length(), 'cm');
            }

            if ($product->get_width() != '') {
                $dimensions .= '/' . wc_get_dimension($product->get_width(), 'cm');
            }

            if ($product->get_height() != '') {
                $dimensions .= '/' . wc_get_dimension($product->get_height(), 'cm');
            }

            $weight = '';

            if ($product->get_weight() != '') {
                $weight = wc_get_weight($product->get_weight(), 'kg');
            }

            if ($product->is_taxable()) {
                $tax_rates = WC_Tax::get_rates($product->get_tax_class());
                $tax = reset($tax_rates);
            }

            $product_data = array(
                'id' => $product->get_id(),
                'productId' => ($product->get_parent_id() > 0) ? $parent->get_id() : $product->get_id(),
                'name' => $product->get_name(),
                'productName' => ($product->get_parent_id() > 0) ? $parent->get_title() : $product->get_title(),
                'price' => wc_get_price_including_tax($product),
                'picture' => $image[0],
                'url' => ($product->get_parent_id() > 0) ? $parent->get_permalink() : $product->get_permalink(),
                'quantity' => is_null($product->get_stock_quantity()) ? 0 : $product->get_stock_quantity(),
                'categoryId' => $term_list,
                'dimensions' => $dimensions,
                'weight' => $weight,
                'tax' => isset($tax) ? $tax['rate'] : 'none'
            );

            if ($product->get_sku() != '') {
                $params[] = array('code' => 'article', 'name' => 'Артикул', 'value' => $product->get_sku());

                if (isset($this->settings['bind_by_sku']) && $this->settings['bind_by_sku'] == WC_Retailcrm_Base::YES) {
                    $product_data['xmlId'] = $product->get_sku();
                }
            }

            if (!empty($params)) {
                $product_data['params'] = $params;
            }

            if (isset($product_data)) {
                $full_product_list[] = $product_data;
            }

            unset($product_data);
        }

        /**
         * Get product statuses
         *
         * @return array
         */
        private function checkPostStatuses() {
            $status_args = array();

            foreach (get_post_statuses() as $key => $value) {
                if (isset($this->settings['p_' . $key]) && $this->settings['p_' . $key] == WC_Retailcrm_Base::YES) {
                    $status_args[] = $key;
                }
            }

            return $status_args;
        }
    }

endif;
