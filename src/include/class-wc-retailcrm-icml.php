<?php

if (!class_exists('WC_Retailcrm_Icml')) :
    /**
     * PHP version 7.0
     *
     * Class WC_Retailcrm_Icml - Generate ICML file (catalog).
     *
     * @category Integration
     * @author   RetailCRM <integration@retailcrm.ru>
     * @license  http://retailcrm.ru Proprietary
     * @link     http://retailcrm.ru
     * @see      http://help.retailcrm.ru
     */
    class WC_Retailcrm_Icml
    {
        const OFFER_PROPERTIES = [
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

        protected $shop;
        protected $file;
        protected $tmpFile;
        protected $settings;
        protected $icmlWriter;

        /**
         * WC_Retailcrm_Icml constructor.
         *
         */
        public function __construct()
        {
            $this->shop       = get_bloginfo('name');
            $this->file       = ABSPATH . 'simla.xml';
            $this->tmpFile    = sprintf('%s.tmp', $this->file);
            $this->settings   = get_option(WC_Retailcrm_Base::$option_key);
            $this->icmlWriter = new WC_Retailcrm_Icml_Writer($this->tmpFile);
        }

        /**
         * Generate ICML catalog.
         */
        public function generate()
        {
            $this->icmlWriter->writeHead($this->shop);

            $categories = $this->prepareCategories();

            if (empty($categories)) {
                writeBaseLogs('Can`t get categories!');
                return;
            }

            $this->icmlWriter->writeCategories($categories);

            $offers = $this->prepareOffers();

            if (empty($offers)) {
                writeBaseLogs('Can`t get offers!');
                return;
            }

            $this->icmlWriter->writeOffers($offers);

            $this->icmlWriter->writeEnd();
            $this->icmlWriter->formatXml($this->tmpFile);

            rename($this->tmpFile, $this->file);
        }

        /**
         * Prepare WC offers for write.
         *
         * @return void
         */
        private function prepareOffers()
        {
            $productStatuses = $this->getProductStatuses();

            if (!$productStatuses) {
                $productStatuses = ['publish'];
            }

            $page = 1;
            $offerAttributes = $this->getOfferAttributes();

            do {
                $products = wc_get_products(
                    [
                        'limit'    => 1000,
                        'status'   => $productStatuses,
                        'page'     => $page,
                        'paginate' => true,
                    ]
                );

                // Clearing the object cache after calling the function wc_get_products
                wp_cache_flush();

                if (empty($products)) {
                    writeBaseLogs('Can`t get products!');
                    return;
                }

                foreach ($products->products as $offer) {
                    $type = $offer->get_type();

                    if (strpos($type, 'variable') !== false || strpos($type, 'variation') !== false) {
                        foreach ($offer->get_children() as $childId) {
                            $childProduct = wc_get_product($childId);

                            if (!$childProduct) {
                                continue;
                            }

                            yield $this->getOffer($offerAttributes, $childProduct, $offer);
                        }
                    } else {
                        yield $this->getOffer($offerAttributes, $offer);
                    }
                }

                $page++;
            } while ($page <= $products->max_num_pages);
        }

        /**
         * Get WC offer attributes.
         *
         * @return array
         */
        private function getOfferAttributes()
        {
            $offerAttributes   = [];
            $attributeTaxonomies = wc_get_attribute_taxonomies();

            foreach ($attributeTaxonomies as $productAttribute) {
                $attributeId = wc_attribute_taxonomy_name_by_id(intval($productAttribute->attribute_id));
                $offerAttributes[$attributeId] = $productAttribute->attribute_label;
            }

            return $offerAttributes;
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
                $productDescription = $this->getOfferDescription($product);

                if (empty($productDescription) && $parent instanceof WC_Product_Variable) {
                    $this->getOfferDescription($parent);
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
         * Get product statuses.
         *
         * @return array
         */
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

        /**
         * Get offer description.
         *
         * @param WC_Product | WC_Product_Variable $product WC product.
         *
         * @return string
         */
        private function getOfferDescription($product)
        {
            return $this->settings['product_description'] == 'full'
                ? $product->get_description()
                : $product->get_short_description();
        }

        /**
         * Prepare WC categories for write.
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

            $wcTerms = get_categories($args);

            foreach ($wcTerms as $term) {
                $category = [
                    'id' => $term->term_id,
                    'parentId' => $term->parent,
                    'name' => $term->name
                ];

                $thumbnailId = function_exists('get_term_meta')
                    ? get_term_meta($term->term_id, 'thumbnail_id', true)
                    : get_woocommerce_term_meta($term->term_id, 'thumbnail_id', true);

                $picture = wp_get_attachment_url($thumbnailId);

                if ($picture) {
                    $category['picture'] = $picture;
                }

                $categories[] = $category;
            }

            return $categories;
        }
    }
endif;
