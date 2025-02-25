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
            $ids = $_GET['order_ids_retailcrm'];

            WC_Retailcrm_Logger::info(__METHOD__, 'Selected order IDs: ' . json_encode($ids));

            if (!empty($ids)) {
                preg_match_all('/\d+/', $ids, $matches);

                if (!empty($matches[0])) {
                    $this->uploadArchiveOrders(null, $matches[0]);
                }
            }
        }

        /**
         * Uploads archive order in CRM
         *
         * @param int|null $page Number page uploads.
         * @param array $ids  Ids orders upload.
         *
         * @return void|null
         * @throws Exception Invalid argument exception.
         */
        public function uploadArchiveOrders(?int $page, array $ids = [])
        {
            if (!$this->retailcrm instanceof WC_Retailcrm_Proxy) {
                return null;
            }

            $orderIds = [];

            if (null !== $page) {
                $orderIds = $this->getCmsOrders($page);
            } elseif ([] !== $ids) {
                $orderIds = $ids;
            }

            if ($orderIds === []) {
                return null;
            }

            WC_Retailcrm_Logger::info(__METHOD__, 'Archive order IDs: ' . implode(', ', $ids));

            [$ordersForUpload, $uploadErrors] = $this->orders->processOrderForUpload($orderIds);

            try {
                $response = $this->retailcrm->ordersUpload($ordersForUpload);

                if (!$response->isSuccessful()) {
                    throw new RuntimeException(
                        sprintf(
                            'Failure to upload orders: %s. Status code: %s',
                            $response->getErrorString(),
                            $response->getStatusCode()
                        )
                    );
                }
            } catch (Exception $exception) {
                WC_Retailcrm_Logger::error(
                    __METHOD__,
                    sprintf("Error while uploading orders: %s", $exception->getMessage())
                );

                return null;
            }

            /** WP version >= 6 */
            if (function_exists('wp_cache_flush_runtime')) {
                wp_cache_flush_runtime();
            } else {
                wp_cache_flush();
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

            if ($users !== []) {
                $dataCustomers = [];

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
         *
         * @return mixed
         */
        private function getCmsOrders($page)
        {
            return wc_get_orders(
                [
                    'type' => wc_get_order_types('view-orders'),
                    'limit' => self::RETAILCRM_COUNT_OBJECT_UPLOAD,
                    'status' => array_keys(wc_get_order_statuses()),
                    'offset' => self::RETAILCRM_COUNT_OBJECT_UPLOAD * $page,
                    'return' => 'ids',
                ]
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

            if (useHpos()) {
                // Use {$wpdb->prefix}, because wp_wc_orders not standard WP table
                $result = $wpdb->get_results("SELECT COUNT(ID) as `count` FROM {$wpdb->prefix}wc_orders");
            } else {
                $result = $wpdb->get_results("SELECT COUNT(ID) as `count` FROM $wpdb->posts WHERE post_type = 'shop_order'");
            }

            return $result[0]->count ? (int) $result[0]->count : 0;
        }

        /**
         * Return users ids
         *
         * @param integer $page Number page uploads.
         *
         * @return mixed
         */
        private function getCmsUsers(int $page)
        {
            return get_users(
                [
                    'number' => self::RETAILCRM_COUNT_OBJECT_UPLOAD,
                    'offset' => self::RETAILCRM_COUNT_OBJECT_UPLOAD * $page,
                ]
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

            return $userCount['total_users'] ? (int) $userCount['total_users'] : 0;
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
            if ($errors === []) {
                return;
            }

            foreach ($errors as $orderId => $error) {
                WC_Retailcrm_Logger::error(
                    __METHOD__,
                    sprintf("Error while uploading [%d] => %s", $orderId, $error)
                );
            }
        }

        public function uploadConsole($entity, $page = 0)
        {
            $ordersPages = (int) ceil($this->getCountOrders() / 50);
            $customerPages = (int) ceil($this->getCountUsers() / 50);

            try {
                switch ($entity) {
                    case 'orders':
                        $this->archiveUpload('orders', $page, $ordersPages);
                        break;
                    case 'customers':
                        $this->archiveUpload('customers', $page, $customerPages);
                        break;
                    case 'full_upload':
                        $this->archiveUpload('customers', 0, $customerPages);
                        $this->archiveUpload('orders', 0, $ordersPages);
                        break;
                    default:
                        echo 'Unknown entity: ' . $entity;
                }
            } catch (Exception $exception) {
                echo $exception->getMessage();
            }
        }

        public function archiveUpload($entity, $page, $totalPages)
        {
            echo $entity . ' uploading started' . PHP_EOL;

            do {
                if ($entity === 'orders') {
                    $this->uploadArchiveOrders($page);
                } elseif ($entity === 'customers') {
                    $this->uploadArchiveCustomers($page);
                }

                echo $page . ' page uploaded' . PHP_EOL;

                $page++;
            } while ($page <= $totalPages);
        }
    }
}
