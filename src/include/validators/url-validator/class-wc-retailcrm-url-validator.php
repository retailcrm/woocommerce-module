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
        const CRM_DOMAINS_URL = 'https://infra-data.retailcrm.tech/crm-domains.json';
        const BOX_DOMAINS_URL = 'https://infra-data.retailcrm.tech/box-domains.json';

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

                $urlArray = parse_url($filteredUrl);

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
            $existInCrm = $this->checkDomains(self::CRM_DOMAINS_URL, $mainDomain);
            $existInBox = $this->checkDomains(self::BOX_DOMAINS_URL, $crmUrl['host']);

            if (false === $existInCrm && false === $existInBox) {
                throw new ValidatorException($this->domainFail);
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
                throw new ValidatorException($this->noValidUrlHost);
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
                throw new ValidatorException($this->schemeFail);
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
                throw new ValidatorException($this->queryFail);
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
                throw new ValidatorException($this->authFail);
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
                throw new ValidatorException($this->fragmentFail);
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
                throw new ValidatorException($this->portFail);
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
                throw new ValidatorException($this->pathFail);
            }
        }

        /**
         * @param string $domainUrl
         *
         * @return array
         * @throws ValidatorException
         */
        private function getValidDomains(string $domainUrl): array
        {
            try {
                $content = json_decode(file_get_contents($domainUrl), true);

                return array_column($content['domains'], 'domain');
            } catch (Exception $exception) {
                throw new ValidatorException($this->getFileError);
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

        /**
         * @param string $crmDomainsUrl
         * @param string $domainHost
         *
         * @return bool
         * @throws ValidatorException
         */
        private function checkDomains(string $crmDomainsUrl, string $domainHost): bool
        {
            return in_array($domainHost, $this->getValidDomains($crmDomainsUrl), true);
        }
    }
endif;
