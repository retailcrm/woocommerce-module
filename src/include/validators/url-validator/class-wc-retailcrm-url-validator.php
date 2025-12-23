<?php

if (!class_exists('WC_Retailcrm_Url_Validator')) :
    /**
     * PHP version 7.0
     *
     * Class WC_Retailcrm_Url_Validator - Validate CRM URL.
     *
     * @category Integration
     * @author   RetailCRM <integration@retailcrm.ru>
     * @license  http://retailcrm.ru Proprietary
     * @link     http://retailcrm.ru
     * @see      http://help.retailcrm.ru
     */
    class WC_Retailcrm_Url_Validator extends WC_Retailcrm_Url_Constraint
    {
        const CRM_ALL_DOMAINS = ["ecomlogic.com", "retailcrm.ru", "retailcrm.pro", "retailcrm.es", "simla.com", "simla.io", "retailcrm.io"];

        /**
         * @param string $crmUrl
         *
         * @return string
         */
        public function validateUrl(string $crmUrl): string
        {
            $validateMessage = '';

            try {
                $filteredUrl = filter_var($crmUrl, FILTER_VALIDATE_URL);

                if (false === $filteredUrl) {
                    throw new ValidatorException($this->noValidUrl, 400);
                }

                $urlArray = wp_parse_url($filteredUrl);

                if (!is_array($urlArray)) {
                    throw new ValidatorException("Can't parse url in validation", 400);
                }

                $this->validateUrlFormat($urlArray);
                $this->validateUrlDomains($urlArray);
            } catch (ValidatorException $exception) {
                $validateMessage = $exception->getMessage();
            }

            return $validateMessage;
        }

        /**
         * @param array $crmUrl
         *
         * @throws ValidatorException
         */
        private function validateUrlFormat(array $crmUrl)
        {
            $this->checkHost($crmUrl);
            $this->checkScheme($crmUrl);
            $this->checkAuth($crmUrl);
            $this->checkFragment($crmUrl);
            $this->checkPath($crmUrl);
            $this->checkPort($crmUrl);
            $this->checkQuery($crmUrl);
        }

        /**
         * @param array $crmUrl
         *
         * @throws ValidatorException
         */
        private function validateUrlDomains(array $crmUrl)
        {
            $mainDomain = $this->getMainDomain($crmUrl['host']);

            if (!in_array($mainDomain, self::CRM_ALL_DOMAINS, true)) {
                throw new ValidatorException(esc_attr($this->domainFail));
            }
        }

        /**
         * @param array $crmUrl
         *
         * @throws ValidatorException
         */
        private function checkHost(array $crmUrl)
        {
            if (empty($crmUrl['host'])) {
                throw new ValidatorException(esc_attr($this->noValidUrlHost));
            }
        }

        /**
         * @param array $crmUrl
         *
         * @throws ValidatorException
         */
        private function checkScheme(array $crmUrl)
        {
            if (!empty($crmUrl['scheme']) && 'https' !== $crmUrl['scheme']) {
                throw new ValidatorException(esc_attr($this->schemeFail));
            }
        }

        /**
         * @param array $crmUrl
         *
         * @throws ValidatorException
         */
        private function checkQuery(array $crmUrl)
        {
            if (!empty($crmUrl['query'])) {
                throw new ValidatorException(esc_attr($this->queryFail));
            }
        }

        /**
         * @param array $crmUrl
         *
         * @throws ValidatorException
         */
        private function checkAuth(array $crmUrl)
        {
            if (!empty($crmUrl['pass']) || !empty($crmUrl['user'])) {
                throw new ValidatorException(esc_attr($this->authFail));
            }
        }

        /**
         * @param array $crmUrl
         *
         * @throws ValidatorException
         */
        private function checkFragment(array $crmUrl)
        {
            if (!empty($crmUrl['fragment'])) {
                throw new ValidatorException(esc_attr($this->fragmentFail));
            }
        }

        /**
         * @param array $crmUrl
         *
         * @throws ValidatorException
         */
        private function checkPort(array $crmUrl)
        {
            if (!empty($crmUrl['port'])) {
                throw new ValidatorException(esc_attr($this->portFail));
            }
        }

        /**
         * @param array $crmUrl
         *
         * @throws ValidatorException
         */
        private function checkPath(array $crmUrl)
        {
            if (!empty($crmUrl['path']) && '/' !== $crmUrl['path']) {
                throw new ValidatorException(esc_attr($this->pathFail));
            }
        }

        /**
         * @param string $host
         *
         * @return string
         */
        private function getMainDomain(string $host): string
        {
            $hostArray = explode('.', $host);
            unset($hostArray[0]);

            return implode('.', $hostArray);
        }
    }
endif;
