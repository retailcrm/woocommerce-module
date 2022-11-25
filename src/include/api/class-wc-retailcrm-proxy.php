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

        private static function reduceErrors($errors)
        {
            $result = '';

            foreach ($errors as $key => $error) {
                $result .= " [$key] => $error";
            }

            return $result;
        }

        /**
         * Response will be omitted in debug logs for those methods
         *
         * @return string[]
         */
        private function methodsWithoutDebugResponse()
        {
            $methodsList = array('statusesList', 'paymentTypesList', 'deliveryTypesList', 'orderMethodsList');

            foreach ($methodsList as $key => $method) {
                $method = get_class($this->retailcrm) . '::' . $method;
                $methodsList[$key] = $method;
            }

            return $methodsList;
        }

        public function __call($method, $arguments)
        {
            $result = '';
            $response = null;
            $called = sprintf('%s::%s', get_class($this->retailcrm), $method);

            try {
                WC_Retailcrm_Logger::debug(
                    $called,
                    array(empty($arguments) ? '[no params]' : print_r($arguments, true))
                );
                /** @var \WC_Retailcrm_Response $response */
                $response = call_user_func_array(array($this->retailcrm, $method), $arguments);

                if (is_string($response)) {
                    WC_Retailcrm_Logger::debug($called, array($response));
                    return $response;
                }

                if (empty($response)) {
                    WC_Retailcrm_Logger::add(sprintf("[%s] null (no response whatsoever)", $called));
                    return null;
                }

                if ($response->isSuccessful()) {
                    // Don't print long lists in debug logs (errors while calling this will be easy to detect anyway)
                    // Also don't call useless array_map at all while debug mode is off.
                    if (retailcrm_is_debug()) {
                        if (in_array(
                            $called,
                            $this->methodsWithoutDebugResponse()
                        )) {
                            WC_Retailcrm_Logger::debug($called, array('[request was successful, but response is omitted]'));
                        } else {
                            WC_Retailcrm_Logger::debug($called, array($response->getRawResponse()));
                        }
                    }

                    $result = ' Ok';
                } else {
                    $result = sprintf(
                        $called ." : Error: [HTTP-code %s] %s",
                        $response->getStatusCode(),
                        $response->getErrorString()
                    );

                    if (isset($response['errors'])) {
                        $result .= self::reduceErrors($response['errors']);
                    }

                    WC_Retailcrm_Logger::debug($called, array($response->getErrorString()));
                    WC_Retailcrm_Logger::debug($called, array($response->getRawResponse()));
                }

                WC_Retailcrm_Logger::add(sprintf("[%s] %s", $called, $result));
            } catch (WC_Retailcrm_Exception_Curl $exception) {
                WC_Retailcrm_Logger::debug(get_class($this->retailcrm).'::'.$called, array($exception->getMessage()));
                WC_Retailcrm_Logger::debug('', array($exception->getTraceAsString()));
                WC_Retailcrm_Logger::add(sprintf("[%s] %s - %s", $called, $exception->getMessage(), $result));
            } catch (WC_Retailcrm_Exception_Json $exception) {
                WC_Retailcrm_Logger::debug(get_class($this->retailcrm).'::'.$called, array($exception->getMessage()));
                WC_Retailcrm_Logger::debug('', array($exception->getTraceAsString()));
                WC_Retailcrm_Logger::add(sprintf("[%s] %s - %s", $called, $exception->getMessage(), $result));
            } catch (InvalidArgumentException $exception) {
                WC_Retailcrm_Logger::debug(get_class($this->retailcrm).'::'.$called, array($exception->getMessage()));
                WC_Retailcrm_Logger::debug('', array($exception->getTraceAsString()));
                WC_Retailcrm_Logger::add(sprintf("[%s] %s - %s", $called, $exception->getMessage(), $result));
            }

            return !empty($response) ? $response : new WC_Retailcrm_Response(900, '{}');
        }
    }
endif;
