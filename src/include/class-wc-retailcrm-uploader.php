<?php
/**
 * RetailCRM Integration.
 *
 * @package  WC_Retailcrm_Uploader
 * @category Integration
 * @author   RetailCRM
 */

if (class_exists('WC_Retailcrm_Uploader') === false) {
    /**
     * Class WC_Retailcrm_Uploader
     */
    class WC_Retailcrm_Uploader
    {
        const RETAILCRM_COUNT_OBJECT_UPLOAD = 50;

        /**
         * Api client RetailCRM
         *
         * @var WC_Retailcrm_Client_V5
         */
        private $retailcrm;

        /**
         * Orders RetailCRM
         *
         * @var WC_Retailcrm_Orders
         */
        private $orders;

        /**
         * Customers RetailCRM
         *
         * @var WC_Retailcrm_Customers
         */
        private $customers;


        /**
         * WC_Retailcrm_Uploader constructor.
         *
         * @param WC_Retailcrm_Client_V5 $retailcrm Api client RetailCRM.
         * @param WC_Retailcrm_Orders    $orders    Object order RetailCRM.
         * @param WC_Retailcrm_Customers $customers Object customer RetailCRM.
         */
        public function __construct($retailcrm, $orders, $customers)
        {
            $this->retailcrm = $retailcrm;
            $this->orders    = $orders;
            $this->customers = $customers;

        }//end __construct()


        /**
         * Uploads selected order in CRM
         *
         * @return void
         * @throws Exception Invalid argument exception.
         */
        public function uploadSelectedOrders()
        {
            $response = filter_input(INPUT_GET, 'order_ids_retailcrm');

            if (empty($response) === false) {
                $ids = array_unique(explode(',', $response));

                if (empty($ids) === false) {
                    $this->uploadArchiveOrders(0, $ids);
                }
            }

        }//end uploadSelectedOrders()


        /**
         * Uploads archive order in CRM
         *
         * @param integer $page Number page uploads.
         * @param array   $ids  Ids orders upload.
         *
         * @return void
         * @throws Exception Invalid argument exception.
         */
        public function uploadArchiveOrders($page, $ids=array())
        {
            if (is_object($this->retailcrm) === false) {
                return;
            }

            $uploadErrors = array();
            $ordersCms    = $this->getCmsOrders($page, $ids);

            foreach ($ordersCms as $dataOrder) {
                $orderId      = $dataOrder->ID;
                $errorMessage = $this->orders->orderCreate($orderId);

                if (is_string($errorMessage) === true) {
                    $errorMessage = empty($errorMessage) === true ? 'Order exist. External id: ' . $orderId : $errorMessage;
                    $uploadErrors[$orderId] = $errorMessage;
                }
            }

            $this->logOrdersUploadErrors($uploadErrors);

        }//end uploadArchiveOrders()


        /**
         * Uploads archive customer in CRM
         *
         * @param integer $page Number page uploads.
         *
         * @return void
         * @throws Exception Invalid argument exception.
         */
        public function uploadArchiveCustomers($page)
        {
            if (is_object($this->retailcrm) === false) {
                return;
            }

            $users = $this->getCmsUsers($page);

            if (empty($users) === false) {
                $dataCustomers = array();

                foreach ($users as $user) {
                    if ($this->customers->isCustomer($user) === false) {
                        continue;
                    }

                    $customer = new WC_Customer($user->ID);
                    $this->customers->processCustomerForUpload($customer);
                    $dataCustomers[] = $this->customers->getCustomer();
                }

                $this->retailcrm->customersUpload($dataCustomers);
            }//end if

        }//end uploadArchiveCustomers()


        /**
         * Return orders ids
         *
         * @param integer $page Number page uploads.
         * @param array   $ids  Ids orders upload.
         *
         * @return mixed
         */
        private function getCmsOrders($page, $ids=array())
        {
            return get_posts(
                array(
                    'numberposts' => self::RETAILCRM_COUNT_OBJECT_UPLOAD,
                    'offset'      => self::RETAILCRM_COUNT_OBJECT_UPLOAD * $page,
                    'post_type'   => wc_get_order_types('view-orders'),
                    'post_status' => array_keys(wc_get_order_statuses()),
                    'include'     => $ids,
                )
            );

        }//end getOrderIds()


        /**
         * Return users ids
         *
         * @param integer $page Number page uploads.
         *
         * @return mixed
         */
        private function getCmsUsers($page)
        {
            return get_users(
                array(
                    'number' => self::RETAILCRM_COUNT_OBJECT_UPLOAD,
                    'offset' => self::RETAILCRM_COUNT_OBJECT_UPLOAD * $page,
                )
            );

        }//end getCmsUsers()


        /**
         * Array keys must be orders ID's in WooCommerce, values must be strings (error messages).
         *
         * @param array $errors Id order - key and message error - value.
         *
         * @return void
         */
        private function logOrdersUploadErrors($errors)
        {
            if (empty($errors) === true) {
                return;
            }

            WC_Retailcrm_Logger::add('Errors while uploading these orders');

            foreach ($errors as $orderId => $error) {
                WC_Retailcrm_Logger::add(sprintf("[%d] => %s", $orderId, $error));
            }

            WC_Retailcrm_Logger::add('==================================');

        }//end logOrdersUploadErrors()


    }//end class

}//end if
