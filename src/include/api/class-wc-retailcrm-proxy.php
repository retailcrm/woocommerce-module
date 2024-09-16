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
         * Response will be omitted in debug logs for those methods
         *
         * @return string[]
         */
        private function methodsWithoutFullResponse()
        {
            $methodsList = array(
                'statusesList',
                'paymentTypesList',
                'deliveryTypesList',
                'orderMethodsList',
                'storesList'
            );

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
                WC_Retailcrm_Logger::info(
                    $method,
                    empty($arguments) ? '[no params]' : json_encode($arguments), WC_Retailcrm_Logger::TYPE[0]
                );
                /** @var \WC_Retailcrm_Response $response */
                $response = call_user_func_array(array($this->retailcrm, $method), $arguments);

                if (is_string($response)) {
                    WC_Retailcrm_Logger::info($method, $response, WC_Retailcrm_Logger::TYPE[1]);
                    return $response;
                }

                if (empty($response)) {
                    WC_Retailcrm_Logger::error(
                        $method,
                        sprintf("[%s] null (no response whatsoever)", $called),
                        WC_Retailcrm_Logger::TYPE[1]
                    );

                    return null;
                }

                if ($response->isSuccessful()) {
                    // Don't print long lists in debug logs (errors while calling this will be easy to detect anyway)
                    // Also don't call useless array_map at all while debug mode is off.
                    if (in_array(
                        $called,
                        $this->methodsWithoutFullResponse()
                    )) {
                        WC_Retailcrm_Logger::info(
                            $method,
                            'Ok [request was successful, but response is omitted]',
                            WC_Retailcrm_Logger::TYPE[1]
                        );
                    } else {
                        WC_Retailcrm_Logger::info(
                            $method,
                            'Ok ' . $response->getRawResponse(),
                            WC_Retailcrm_Logger::TYPE[1]
                        );
                    }

                } else {
                    WC_Retailcrm_Logger::error($method, sprintf(
                        "Error: [HTTP-code %s] %s %s",
                        $response->getStatusCode(),
                        $response->getErrorString(),
                        $response->getRawResponse()),
                        WC_Retailcrm_Logger::TYPE[1]
                    );
                }
            } catch (WC_Retailcrm_Exception_Curl $exception) {
                WC_Retailcrm_Logger::error($method, $exception->getMessage(), WC_Retailcrm_Logger::TYPE[2]);
            } catch (WC_Retailcrm_Exception_Json $exception) {
                WC_Retailcrm_Logger::error($method, $exception->getMessage(), WC_Retailcrm_Logger::TYPE[2]);
            } catch (InvalidArgumentException $exception) {
                WC_Retailcrm_Logger::error($method, $exception->getMessage(), WC_Retailcrm_Logger::TYPE[2]);
            }

            return !empty($response) ? $response : new WC_Retailcrm_Response(900, '{}');
        }
    }
endif;
