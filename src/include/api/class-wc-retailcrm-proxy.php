<?php
/**
 * RetailCRM Integration.
 *
 * @package  WC_Retailcrm_Proxy
 * @category Integration
 * @author   RetailCRM
 */

if ( ! class_exists( 'WC_Retailcrm_Proxy' ) ) :

    /**
     * Class WC_Retailcrm_Proxy
     */
    class WC_Retailcrm_Proxy
    {
        protected $retailcrm;
        protected $logger;

        public function __construct($api_url, $api_key, $api_vers = null)
        {   
            $this->logger = new WC_Logger();

            if ( ! class_exists( 'WC_Retailcrm_Client_V4' ) ) {
                include_once( __DIR__ . '/class-wc-retailcrm-client-v4.php' );
            }

            if ( ! class_exists( 'WC_Retailcrm_Client_V5' ) ) {
                include_once( __DIR__ . '/class-wc-retailcrm-client-v5.php' );
            }

            switch ($api_vers) {
                case 'v4':
                    $this->retailcrm = new WC_Retailcrm_Client_V4($api_url, $api_key, $api_vers);
                    break;
                case 'v5':
                    $this->retailcrm = new WC_Retailcrm_Client_V5($api_url, $api_key, $api_vers);
                    break;
                case null:
                    $this->retailcrm = new WC_Retailcrm_Client_V4($api_url, $api_key, $api_vers);
                    break;
            }
        }

        public function __call($method, $arguments)
        {
            $response = null;

            try {
                $response = call_user_func_array(array($this->retailcrm, $method), $arguments);

                if ($response->isSuccessful()) {
                    $result = ' Ok';
                } else {
                    $result = sprintf(
                        $method ." : Error: [HTTP-code %s] %s",
                        $response->getStatusCode(),
                        $response->getErrorMsg()
                    );

                    if (isset($response['errors'])) {
                            foreach ($response['errors'] as $error) {
                            $result .= " $error";
                        }
                    }
                }

                $this->logger->add('retailcrm', sprintf("[%s] %s", $method, $result));
            } catch (WC_Retailcrm_Exception_Curl $exception) {
                $this->logger->add('retailcrm', sprintf("[%s] %s - %s", $method, $exception->getMessage(), $result));
            } catch (WC_Retailcrm_Exception_Json $exception) {
                $this->logger->add('retailcrm', sprintf("[%s] %s - %s", $method, $exception->getMessage(), $result));
            } catch (InvalidArgumentException $exception) {
                $this->logger->add('retailcrm', sprintf("[%s] %s - %s", $method, $exception->getMessage(), $result));
            }

            return !empty($response) ? $response : new WC_Retailcrm_Response(900, '{}');
        }
    }
endif;