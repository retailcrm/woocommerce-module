<?php

if (class_exists('WC_Retailcrm_Uploader') === false) {
    /**
     * PHP version 7.0
     *
     * Class WC_Retailcrm_Uploader - Allows upload archival orders/customers in CRM.
     *
     * @category Integration
     * @author   RetailCRM <integration@retailcrm.ru>
     * @license  http://retailcrm.ru Proprietary
     * @link     http://retailcrm.ru
     * @see      http://help.retailcrm.ru
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
        }

        /**
         * Uploads selected order in CRM
         *
         * @return void
         * @throws Exception Invalid argument exception.
         */
        public function uploadSelectedOrders()
        {
            if (!empty($_GET['order_ids_retailcrm'])) {
                $ids = array_unique(explode(',', $_GET['order_ids_retailcrm']));
                
                if (!empty($ids)) {
                    $this->uploadArchiveOrders(0, $ids);
                }
            }
        }

        /**
         * Uploads archive order in CRM
         *
         * @param int     $page Number page uploads.
         * @param array   $ids  Ids orders upload.
         *
         * @return void|null
         * @throws Exception Invalid argument exception.
         */
        public function uploadArchiveOrders($page, $ids = array())
        {
            if (!$this->retailcrm instanceof WC_Retailcrm_Proxy) {
                return null;
            }

            $uploadErrors = array();
            $ordersCms    = $this->getCmsOrders($page, $ids);

            foreach ($ordersCms as $dataOrder) {
                $orderId      = $dataOrder->ID;
                $errorMessage = $this->orders->orderCreate($orderId);

                if (true === is_string($errorMessage)) {
                    $errorMessage = empty($errorMessage) === true
                        ? 'Order exist. External id: ' . $orderId
                        : $errorMessage;
                    $uploadErrors[$orderId] = $errorMessage;
                }
            }

            $this->logOrdersUploadErrors($uploadErrors);
        }

        /**
         * Uploads archive customer in CRM
         *
         * @param integer $page Number page uploads.
         *
         * @return array
         * @throws Exception Invalid argument exception.
         */
        public function uploadArchiveCustomers($page)
        {
            if (!$this->retailcrm instanceof WC_Retailcrm_Proxy) {
                return null;
            }

            $users = $this->getCmsUsers($page);

            if (false === empty($users)) {
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
            }

            return $dataCustomers;
        }

        /**
         * Return orders ids
         *
         * @param integer $page Number page uploads.
         * @param array   $ids  Ids orders upload.
         *
         * @return mixed
         */
        private function getCmsOrders($page, $ids = array())
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
        }

        /**
         * Return count orders
         *
         * @return integer
         */
        public function getCountOrders()
        {
            global $wpdb;

            $result = $wpdb->get_results("SELECT COUNT(ID) as `count` FROM $wpdb->posts WHERE post_type = 'shop_order'");

            return empty($result[0]->count) === false ? (int) $result[0]->count : 0;
        }


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
        }


        /**
         * Return count users
         *
         * @return integer
         */
        public function getCountUsers()
        {
            $userCount = count_users();

            return empty($userCount['total_users']) === false ? $userCount['total_users'] : 0;
        }


        /**
         * Array keys must be orders ID's in WooCommerce, values must be strings (error messages).
         *
         * @param array $errors Id order - key and message error - value.
         *
         * @return void
         *
         *  @codeCoverageIgnore
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
        }
    }
}//end if
