<?php

/**
 * PHP version 5.6
 *
 * Class WC_Retailcrm_Icml - Generate ICML file (catalog).
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */

if (!class_exists('WC_Retailcrm_Icml')) :
    class WC_Retailcrm_Icml
    {
        protected $shop;
        protected $file;
        protected $tmpFile;

        protected $properties = [
            'name',
            'productName',
            'price',
            'purchasePrice',
            'vendor',
            'picture',
            'url',
            'xmlId',
            'productActivity'
        ];

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
            $this->shop = get_bloginfo('name');
            $this->file = ABSPATH . 'simla.xml';
            $this->tmpFile = sprintf('%s.tmp', $this->file);
        }

        /**
         * Generate file
         */
        public function generate()
        {
            $start  = microtime(true);
            $memory = memory_get_usage();

            $categories = $this->prepareCategories();

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

                $this->prepareProducts();

                $dom = dom_import_simplexml(simplexml_load_file($this->tmpFile))->ownerDocument;

                $dom->formatOutput = true;

                $formatted = $dom->saveXML();

                unset($dom, $this->xml);

                file_put_contents($this->tmpFile, $formatted);
                rename($this->tmpFile, $this->file);
            } catch (Exception $e) {
                unlink($this->tmpFile);
            }

            WC_Retailcrm_Logger::debug(__METHOD__, ['Count products: ', $this->getCountProducts()]);
            WC_Retailcrm_Logger::debug(__METHOD__, ['Time test:', 'Time: ' . round(microtime(true) - $start, 2) . ' seconds']);
            WC_Retailcrm_Logger::debug(__METHOD__, ['Memory test:', 'Memory: ' . (memory_get_usage() - $memory) . ' byte']);
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
                html_entity_decode($this->shop)
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
         * @param $offer
         */
        private function writeOffer($offer)
        {
                $this->xml = $this->loadXml();

                $this->offers = $this->xml->shop->offers;
                $this->addOffer($offer);

                $this->xml->asXML($this->tmpFile);
        }

        /**
         * Add categories
         *
         * @param $categories
         */
        private function addCategories($categories)
        {
            $categories = self::filterRecursive($categories);

            foreach ($categories as $category) {
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
        private function addOffer($offers)
        {
            //$offers = self::filterRecursive($offers);

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

                if (array_key_exists('picture', $offer) && !empty($offer['picture'])) {
                    foreach ($offer['picture'] as $urlImage) {
                        $e->addChild('picture', $urlImage);
                    }
                }

                unset($offer['id'], $offer['productId'], $offer['categoryId'], $offer['quantity'], $offer['picture']);
                array_walk($offer, [$this, 'setOffersProperties'], $e);

                if (array_key_exists('params', $offer) && !empty($offer['params'])) {
                    array_walk($offer['params'], [$this, 'setOffersParams'], $e);
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
            }
        }

        /**
         * Set offer properties
         *
         * @param $value
         * @param $key
         * @param $e
         */
        private function setOffersProperties($value, $key, &$e)
        {
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
        private function setOffersParams($value, $key, &$e)
        {
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

                if (
                    is_null($haystack[$key])
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
        private function prepareProducts()
        {
            $statusArgs = $this->getProductStatuses();

            if (!$statusArgs) {
                $statusArgs = ['publish'];
            }

            $productAttributes   = [];
            $attributeTaxonomies = wc_get_attribute_taxonomies();

            foreach ($attributeTaxonomies as $productAttribute) {
                $attributeId = wc_attribute_taxonomy_name_by_id(intval($productAttribute->attribute_id));
                $productAttributes[$attributeId] = $productAttribute->attribute_label;
            }

            $page = 0;

            do {
                $products = wc_get_products(
                    [
                        'limit'    => 1000,
                        'status'   => $statusArgs,
                        'paginate' => true,
                    ]
                );

                if (empty($products)) {
                    writeBaseLogs('Can`t get products!');
                    return;
                }

                $offer = $this->prepareOffer($products->products, $productAttributes);
                $this->writeOffer($offer);

                $page++;
            } while ($products->max_num_pages > $page);
        }

        /**
         * @param $products
         * @param $productAttributes
         *
         * @return Generator
         */
        private function prepareOffer($products, $productAttributes)
        {
            foreach ($products as $offer) {
                $type = $offer->get_type();

                if (strpos($type, 'variable') !== false || strpos($type, 'variation') !== false) {
                    foreach ($offer->get_children() as $childId) {
                        $childProduct = wc_get_product($childId);

                        if (!$childProduct) {
                            continue;
                        }

                        yield $this->getOffer($productAttributes, $childProduct, $offer);
                    }
                } else {
                    yield $this->getOffer($productAttributes, $offer);
                }
            }
        }

        /**
         * Get WC categories
         *
         * @return array
         */
        private function prepareCategories()
        {
            $categories   = [];
            $taxonomy     = 'product_cat';
            $orderby      = 'parent';
            $show_count   = 0;      // 1 for yes, 0 for no
            $pad_counts   = 0;      // 1 for yes, 0 for no
            $hierarchical = 1;      // 1 for yes, 0 for no
            $title        = '';
            $empty        = 0;

            $args = [
                'taxonomy'     => $taxonomy,
                'orderby'      => $orderby,
                'show_count'   => $show_count,
                'pad_counts'   => $pad_counts,
                'hierarchical' => $hierarchical,
                'title_li'     => $title,
                'hide_empty'   => $empty
            ];

            $wcatTerms = get_categories($args);

            foreach ($wcatTerms as $term) {
                $category = [
                    'id' => $term->term_id,
                    'parentId' => $term->parent,
                    'name' => $term->name
                ];

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
         * Get offer for ICML catalog
         *
         * @param array $productAttributes
         * @param WC_Product $product
         * @param bool | WC_Product_Variable $parent
         *
         * @return array
         */
        private function getOffer(array $productAttributes, WC_Product $product, $parent = false)
        {
            $idImages = array_merge([$product->get_image_id()], $product->get_gallery_image_ids());

            if ($parent !== false && empty(get_the_post_thumbnail_url($product->get_id()))) {
                $idImages = array_merge([$parent->get_image_id()], $parent->get_gallery_image_ids());
            }

            $images = [];

            foreach ($idImages as $id) {
                $images[] = wp_get_attachment_image_src($id, 'full')[0];
            }

            $termList = $parent !== false
                ? $parent->get_category_ids()
                : $product->get_category_ids();

            $attributes = $parent !== false
                ? get_post_meta($parent->get_id(), '_product_attributes')
                : get_post_meta($product->get_id(), '_product_attributes');

            // All attributes are in the first element of the array
            $attributes = (isset($attributes[0])) ? $attributes[0] : $attributes;

            $params = [];

            if (!empty($attributes)) {
                foreach ($attributes as $attributeName => $attribute) {
                    $attributeValue = $product->get_attribute($attributeName);
                    if ($attribute['is_visible'] == 1 && !empty($attributeValue)) {
                        $params[] = [
                            'code'  => $attributeName,
                            'name'  => $productAttributes[$attributeName],
                            'value' => $attributeValue
                        ];
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

            if ($product->get_manage_stock() == true) {
                $stockQuantity = $product->get_stock_quantity();
                $quantity = empty($stockQuantity) === false ? $stockQuantity : 0;
            } else {
                $quantity = $product->get_stock_status() === 'instock' ? 1 : 0;
            }

            $productData = [
                'id' => $product->get_id(),
                'productId' => ($product->get_parent_id() > 0) ? $parent->get_id() : $product->get_id(),
                'name' => $product->get_name(),
                'productName' => ($product->get_parent_id() > 0) ? $parent->get_title() : $product->get_title(),
                'price' => wc_get_price_including_tax($product),
                'picture' => $images,
                'url' => ($product->get_parent_id() > 0) ? $parent->get_permalink() : $product->get_permalink(),
                'quantity' => $quantity,
                'categoryId' => $termList,
                'dimensions' => $dimensions,
                'weight' => $weight,
                'tax' => isset($tax) ? $tax['rate'] : 'none'
            ];

            if ($product->get_sku() != '') {
                $params[] = ['code' => 'article', 'name' => 'Article', 'value' => $product->get_sku()];

                if (isset($this->settings['bind_by_sku']) && $this->settings['bind_by_sku'] == WC_Retailcrm_Base::YES) {
                    $productData['xmlId'] = $product->get_sku();
                }
            }

            if (isset($this->settings['product_description'])) {
                $productDescription = $this->getDescription($product);

                if (empty($productDescription) && $parent instanceof WC_Product_Variable) {
                    $this->getDescription($parent);
                }

                if ($productDescription != '') {
                    $params[] = ['code' => 'description', 'name' => 'Description', 'value' => $productDescription];
                }
            }

            if (!empty($params)) {
                $productData['params'] = $params;
            }

            $productData = apply_filters(
                'retailcrm_process_offer',
                WC_Retailcrm_Plugin::clearArray($productData),
                $product
            );

            if (isset($productData)) {
                return $productData;
            }
        }

        /**
         * Get product statuses
         *
         * @return array
         */
        private function getProductStatuses()
        {
            $statusArgs = [];

            foreach (get_post_statuses() as $key => $value) {
                if (isset($this->settings['p_' . $key]) && $this->settings['p_' . $key] == WC_Retailcrm_Base::YES) {
                    $statusArgs[] = $key;
                }
            }

            return $statusArgs;
        }

        /**
         * Get product description
         *
         * @param WC_Product | WC_Product_Variable $product WC product.
         *
         * @return string
         */
        private function getDescription($product)
        {
            return $this->settings['product_description'] == 'full'
                ? $product->get_description()
                : $product->get_short_description();
        }

        private function getCountProducts()
        {
            global $wpdb;

            return $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE `post_type` LIKE 'product'");
        }
    }

endif;
