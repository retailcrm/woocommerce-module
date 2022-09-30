<?php

if (!class_exists('WC_Retailcrm_Icml_Writer')) :
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
    class WC_Retailcrm_Icml_Writer
    {
        private $writer;

        public function __construct($tmpFile)
        {
            $this->writer = new \XMLWriter();
            $this->writer->openUri($tmpFile);
        }

        /**
         * Write HEAD in ICML catalog.
         *
         * @param string $shop
         *
         * @return void
         */
        public function writeHead(string $shop)
        {
            $this->writer->startDocument('1.0', 'UTF-8');
            $this->writer->startElement('yml_catalog'); // start <yml_catalog>
            $this->writer->writeAttribute('date', date('Y-m-d H:i:s'));
            $this->writer->startElement('shop'); // start <shop>
            $this->writer->WriteElement('name', $shop);
        }

        /**
         * Write categories in ICML catalog.
         *
         * @param array $categories
         *
         * @return void
         */
        public function writeCategories(array $categories)
        {
            $this->writer->startElement('categories'); // start <categories>

            $this->addCategories($categories);

            $this->writer->endElement(); // end </categories>
        }

        /**
         * Add category in ICML catalog.
         *
         * @param array $categories
         *
         * @return void
         */
        private function addCategories(array $categories)
        {
            foreach ($categories as $category) {
                if (!array_key_exists('name', $category) || !array_key_exists('id', $category)) {
                    continue;
                }

                $this->writer->startElement('category'); // start <category>

                $this->writer->writeAttribute('id', $category['id']);

                if (array_key_exists('parentId', $category) && 0 < $category['parentId']) {
                    $this->writer->writeAttribute('parentId', $category['parentId']);
                }

                $this->writer->writeElement('name', $category['name']);

                if (array_key_exists('picture', $category) && $category['picture']) {
                    $this->writer->writeElement('picture', $category['picture']);
                }

                $this->writer->endElement(); // end </category>
            }
        }

        /**
         * Write offers in ICML catalog.
         *
         * @return void
         */
        public function writeOffers($offers)
        {
            $this->writer->startElement('offers'); // start <offers>

            $this->addOffers($offers);

            $this->writer->endElement(); // end </offers>
        }

        /**
         * Add offer in ICML catalog.
         *
         * @return void
         */
        private function addOffers($offers)
        {
            foreach ($offers as $offer) {
                if (!array_key_exists('id', $offer)) {
                    continue;
                }

                $this->writer->startElement('offer'); // start <offer>

                if (!array_key_exists('productId', $offer) || empty($offer['productId'])) {
                    $offer['productId'] = $offer['id'];
                }

                $this->writer->writeAttribute('id', $offer['id']);
                $this->writer->writeAttribute('productId', $offer['productId']);
                $this->writer->writeAttribute('quantity', (int) $offer['quantity'] ?? 0);

                if (isset($offer['categoryId'])) {
                    if (is_array($offer['categoryId'])) {
                        foreach ($offer['categoryId'] as $categoryId) {
                            $this->writer->writeElement('categoryId', $categoryId);
                        }
                    } else {
                        $this->writer->writeElement('categoryId', $offer['$categoryId']);
                    }
                }

                if (!empty($offer['picture'])) {
                    foreach ($offer['picture'] as $urlImage) {
                        $this->writer->writeElement('picture', $urlImage);
                    }
                }

                if (empty($offer['name'])) {
                    $offer['name'] = __('Untitled', 'retailcrm');
                }

                if (empty($offer['productName'])) {
                    $offer['productName'] = $offer['name'];
                }

                unset($offer['id'], $offer['productId'], $offer['categoryId'], $offer['quantity'], $offer['picture']);

                $this->writeOffersProperties($offer);

                if (!empty($offer['params'])) {
                    $this->writeOffersParams($offer['params']);
                }

                if (!empty($offer['dimensions'])) {
                    $this->writer->writeElement('dimensions', $offer['dimensions']);
                }

                if (!empty($offer['weight'])) {
                    $this->writer->writeElement('weight', $offer['weight']);
                }

                if (!empty($offer['tax'])) {
                    $this->writer->writeElement('vatRate', $offer['tax']);
                }

                $this->writer->endElement(); // end </offer>
            }
        }

        /**
         * Set offer properties.
         *
         * @param array $offerProperties
         *
         * @return void
         */
        private function writeOffersProperties(array $offerProperties)
        {
            foreach ($offerProperties as $key => $value) {
                if (!in_array($key, WC_Retailcrm_Icml::OFFER_PROPERTIES)) {
                    continue;
                }

                if (is_array($value)) {
                    foreach ($value as $element) {
                        $this->writer->writeElement($key, $element);
                    }
                } else {
                    $this->writer->writeElement($key, $value);
                }
            }
        }

        /**
         * Set offer params.
         *
         * @param array $offerParams
         *
         * @return void
         */
        private function writeOffersParams(array $offerParams)
        {
            foreach ($offerParams as $param) {
                if (
                    empty($param['code'])
                    || empty($param['name'])
                    || empty($param['value'])
                ) {
                    continue;
                }

                $this->writer->startElement('param'); // start <param>

                $this->writer->writeAttribute('code', $param['code']);
                $this->writer->writeAttribute('name', $param['name']);
                $this->writer->text($param['value']);

                $this->writer->endElement(); // end </param>
            }
        }

        /**
         * Write end tags in ICML catalog.
         *
         * @return void
         */
        public function writeEnd()
        {
            $this->writer->endElement(); // end </yml_catalog>
            $this->writer->endElement(); // end </shop>
            $this->writer->endDocument();
        }

        /**
         * Save ICML catalog.
         *
         * @return void
         */
        public function formatXml($tmpfile)
        {
            $dom = dom_import_simplexml(simplexml_load_file($tmpfile))->ownerDocument;
            $dom->formatOutput = true;
            $formatted = $dom->saveXML();

            unset($dom, $this->writer);

            file_put_contents($tmpfile, $formatted);
        }
    }
endif;
