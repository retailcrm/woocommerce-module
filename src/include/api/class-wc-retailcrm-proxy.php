<?php

if (!class_exists('WC_Retailcrm_Proxy')) :
    /**
     * PHP version 7.0
     *
     * Class WC_Retailcrm_Proxy - RetailCRM Integration.
     *
     * @category Integration
     * @package  WC_Retailcrm_Proxy
     * @author   RetailCRM <dev@retailcrm.ru>
     * @license  https://opensource.org/licenses/MIT MIT License
     * @link     http://retailcrm.ru/docs/Developers/ApiVersion5
     */
    class WC_Retailcrm_Proxy
    {
        protected $retailcrm;
        protected $corporateEnabled;

        public function __construct($api_url, $api_key, $corporateEnabled = false)
        {
            $this->corporateEnabled = $corporateEnabled;

            if ( ! class_exists( 'WC_Retailcrm_Client_V5' ) ) {
                include_once(WC_Integration_Retailcrm::checkCustomFile('include/api/class-wc-retailcrm-client-v5.php'));
            }

            $this->retailcrm = new WC_Retailcrm_Client_V5($api_url, $api_key);
        }

        /**
         * getCorporateEnabled
         *
         * @return bool
         */
        public function getCorporateEnabled()
        {
            return $this->corporateEnabled;
        }

        /**
         * Response will be omitted in logs for those methods
         *
         * @return string[]
         */
        private function methodsWithoutFullLog()
        {
            $methodsList = [
                'statusesList',
                'paymentTypesList',
                'deliveryTypesList',
                'orderMethodsList',
                'storesList',
            ];

            foreach ($methodsList as $key => $method) {
                $method = get_class($this->retailcrm) . '::' . $method;
                $methodsList[$key] = $method;
            }

            return $methodsList;
        }

        public function __call($method, $arguments)
        {
            $response = null;
            $called = sprintf('%s::%s', get_class($this->retailcrm), $method);

            try {
                /** @var \WC_Retailcrm_Response $response */
                $response = $this->getResponse($method, $arguments);

                if (is_string($response)) {
                    WC_Retailcrm_Logger::info($method, $response, [], WC_Retailcrm_Logger::RESPONSE);

                    return $response;
                }

                if (!$response instanceof WC_Retailcrm_Response) {
                    WC_Retailcrm_Logger::error(
                        $method,
                        sprintf("[%s] null (no response whatsoever)", $called),
                        [],
                        WC_Retailcrm_Logger::RESPONSE
                    );

                    return null;
                }

                $this->logResponse($response, $method, $called);
            } catch (WC_Retailcrm_Exception_Curl|WC_Retailcrm_Exception_Json|InvalidArgumentException $exception) {
                WC_Retailcrm_Logger::exception($method, $exception);
            }

            return $response instanceof WC_Retailcrm_Response ? $response : new WC_Retailcrm_Response(900, '{}');
        }

        private function getResponse($method, $arguments)
        {
            WC_Retailcrm_Logger::info(
                $method,
                count($arguments) === 0 ? '[no params]' : '[with params]',
                ['params' => $arguments],
                WC_Retailcrm_Logger::REQUEST
            );

            return call_user_func_array(array($this->retailcrm, $method), $arguments);
        }

        private function logResponse(WC_Retailcrm_Response $response, $method, $called): void
        {
            if ($response->isSuccessful()) {
                if (in_array($called, $this->methodsWithoutFullLog())) {
                    WC_Retailcrm_Logger::info(
                        $method,
                        'Ok',
                        ['body' => 'request was successful, but response is omitted'],
                        WC_Retailcrm_Logger::RESPONSE
                    );
                } else {
                    WC_Retailcrm_Logger::info(
                        $method,
                        'Ok',
                        ['body' => json_decode($response->getRawResponse(), true)],
                        WC_Retailcrm_Logger::RESPONSE
                    );
                }

            } else {
                WC_Retailcrm_Logger::error(
                    $method,
                    sprintf(
                        "Error: [HTTP-code %s] %s",
                        $response->getStatusCode(),
                        $response->getErrorString()
                    ),
                    ['response' => json_decode($response->getRawResponse(), true)],
                    WC_Retailcrm_Logger::RESPONSE
                );
            }
        }
    }
endif;
