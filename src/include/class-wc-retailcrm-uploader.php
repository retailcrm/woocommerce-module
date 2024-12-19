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
         * @param null|int $page Number page uploads.
         * @param array    $ids  Ids orders upload.
         *
         * @return void|null
         * @throws Exception Invalid argument exception.
         */
        public function uploadArchiveOrders($page, $ids = [])
        {
            WC_Retailcrm_Logger::info(__METHOD__, 'Archive order IDs: ' . implode(', ', $ids));

            if (!$this->retailcrm instanceof WC_Retailcrm_Proxy) {
                return null;
            }

            $orderIds = [];
            $uploadErrors = [];

            if (null !== $page) {
                $orderIds = $this->getCmsOrders($page);
            } elseif ([] !== $ids) {
                $orderIds = $ids;
            }

            foreach ($orderIds as $orderId) {
                $errorMessage = $this->orders->orderCreate($orderId);

                if (is_string($errorMessage)) {
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
            if (empty($errors) === true) {
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
            $ordersCount = $this->getCountOrders();
            $customerCount = $this->getCountUsers();

            $ordersPages = (int)($ordersCount / 50) + (($ordersCount % 50 === 0) ? -1 : 0);
            $customerPages = (int)($customerCount / 50) + (($customerCount % 50 === 0) ? -1 : 0);

            echo $customerPages;

            try {
                switch ($entity) {
                    case 'orders':
                        $this->ArchiveUpload('orders', $page, $ordersPages);
                        break;
                    case 'customers':
                        $this->ArchiveUpload('customers', $page, $customerPages);;
                        break;
                    case 'full_upload':
                        $this->ArchiveUpload('orders', 0, $ordersPages);
                        $this->ArchiveUpload('customers', 0, $customerPages);
                        break;
                    default:
                        echo 'Unknown entity: ' . $entity;
                }
            } catch (Exception $exception) {
                echo $exception->getMessage();
            }
        }

        public function archiveUpload($entity, $page, $countPages)
        {
            for ($i = $page; $i <= $countPages; $i++) {
                if ($entity === 'orders') {
                    $this->uploadArchiveOrders($i);
                    echo $page . ' page uploaded' . PHP_EOL;
                } elseif ($entity === 'customers') {
                    $this->uploadArchiveCustomers($i);
                    echo $page . ' page uploaded' . PHP_EOL;
                }
            }
        }
    }
}
