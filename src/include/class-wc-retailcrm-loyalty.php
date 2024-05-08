<?php

if (!class_exists('WC_Retailcrm_Loyalty')) :
    if (!class_exists('WC_Retailcrm_Loyalty_Form')) {
        include_once(WC_Integration_Retailcrm::checkCustomFile('include/components/class-wc-retailcrm-loyalty-form.php'));
    }
    /**
     * PHP version 7.0
     *
     * Class WC_Retailcrm_Loyalty - Allows transfer data carts with CMS.
     *
     * @category Integration
     * @author   RetailCRM <integration@retailcrm.ru>
     * @license  http://retailcrm.ru Proprietary
     * @link     http://retailcrm.ru
     * @see      http://help.retailcrm.ru
     */
    class WC_Retailcrm_Loyalty
    {
        /** @var WC_Retailcrm_Client_V5 */
        protected $apiClient;

        protected $dateFormat;

        protected $settings;

        /** @var WC_Retailcrm_Loyalty_Form */
        protected $loyaltyForm;

        public function __construct($apiClient, $settings)
        {
            $this->apiClient = $apiClient;
            $this->settings = $settings;
            $this->dateFormat = 'Y-m-d H:i:sP';
            $this->loyaltyForm = new WC_Retailcrm_Loyalty_Form();
        }

        public function getForm(int $userId)
        {
            $result = [];

            try {
                $response = $this->getLoyaltyAccounts($userId);
            } catch (Throwable $exception) {
                writeBaseLogs('Exception get loyalty accounts: ' . $exception->getMessage());

                return $result;
            }

            $loyaltyAccount = $response['loyaltyAccounts'][0] ?? null;

            if ($loyaltyAccount && (int) $loyaltyAccount['customer']['externalId'] === $userId) {
                if ($loyaltyAccount['active'] === true) {
                    $result['form'] = $this->loyaltyForm->getInfoLoyalty($loyaltyAccount);
                } else {
                    $result['form'] = $this->loyaltyForm->getActivationForm();

                    $result['loyaltyId'] = $loyaltyAccount['id'];
                }
            } else {
                $result['form'] = $this->loyaltyForm->getRegistrationForm();
            }

           return $result;
        }

        public function registerCustomer(int $userId, string $phone, string $site): bool
        {
            $parameters = [
                'phoneNumber' => $phone,
                'customer' => [
                    'externalId' => $userId
                ]
            ];

            try {
                $response = $this->apiClient->createLoyaltyAccount($parameters, $site);

                if (!$response->isSuccessful()) {
                    writeBaseLogs('Error while registering in the loyalty program: ' . $response->getRawResponse());
                }

                return $response->isSuccessful();
            } catch (Throwable $exception) {
                writeBaseLogs('Exception while registering in the loyalty program: ' . $exception->getMessage());

                return false;
            }
        }

        public function activateLoyaltyCustomer(int $loyaltyId)
        {
            try {
                $response = $this->apiClient->activateLoyaltyAccount($loyaltyId);

                if (!$response->isSuccessful()) {
                    writeBaseLogs('Error while registering in the loyalty program: ' . $response->getRawResponse());
                }

                return $response->isSuccessful();
            } catch (Throwable $exception) {
                writeBaseLogs('Exception while activate loyalty account: ' . $exception->getMessage());

                return false;
            }
        }

        private function getDiscountLp($cartItems, $site, $customerId)
        {
            $order = [
              'site' => $site,
              'customer' => ['externalId' => $customerId],
              'privilegeType' => 'loyalty_level'
            ];

            $useXmlId = isset($this->settings['bind_by_sku']) && $this->settings['bind_by_sku'] === WC_Retailcrm_Base::YES;

            foreach ($cartItems as $item) {
                $product = $item['data'];

                $order['items'][] = [
                    'offer' => $useXmlId ? ['xmlId' => $product->get_sku()] : ['externalId' => $product->get_id()],
                    'quantity' => $item['quantity'],
                    'initialPrice' => wc_get_price_including_tax($product),
                    'discountManualAmount' => ($item['line_subtotal'] - $item['line_total']) / $item['quantity']
                ];
            }

            $response = $this->apiClient->calculateDiscountLoyalty($site, $order);

            if (!$response->isSuccessful() || !isset($response['calculations'])) {
                return 0;
            }

            $discount = 0;

            //Checking if the loyalty discount is a percentage discount
            foreach ($response['order']['items'] as $item) {
                if (!isset($item['discounts'])) {
                    continue;
                }

                foreach ($item['discounts'] as $discountItem) {
                    if ($discountItem['type'] === 'loyalty_level') {
                        $discount += $discountItem['amount'];
                    }
                }
            }

            //If the discount has already been given, do not work with points deduction
            if ($discount === 0) {
                foreach ($response['calculations'] as $calculate) {
                    if ($calculate['privilegeType'] !== 'loyalty_level') {
                        continue;
                    }

                    $discount = $calculate['maxChargeBonuses'];
                }
            }

            return $discount;
        }

        private function getLoyaltyAccounts(int $userId)
        {
            $response = $this->apiClient->customersGet($userId);

            if (!isset($response['customer']['id'])) {
                return [];
            }

            $filter['customerId'] = $response['customer']['id'];

            $response = $this->apiClient->getLoyaltyAccountList($filter);

            if (!$response->isSuccessful() || !$response->offsetExists('loyaltyAccounts')) {
                return null;
            }

            return $response;
        }

        public function createLoyaltyCoupon($refreshCoupon = false)
        {
            global $woocommerce;

            $site = $this->apiClient->getSingleSiteForKey();
            $cartItems = $woocommerce->cart->get_cart();
            $customerId = $woocommerce->customer ? $woocommerce->customer->get_id() : null;

            $resultString = '';

            if (!$customerId || !$cartItems) {
                return null;
            }

            $couponsLp = [];
            // Check exists used loyalty coupons
            foreach ($woocommerce->cart->get_coupons() as $code => $coupon) {
                if (preg_match('/^pl\d+$/m', $code) === 1) {
                    $couponsLp[] = $code;
                }
            }

            // if you need to refresh coupon that does not exist
            if (count($couponsLp) === 0 && $refreshCoupon) {
                return null;
            }

            //If one loyalty coupon is used, not generate a new one
            if (count($couponsLp) === 1 && !$refreshCoupon) {
                return null;
            }

            // if more than 1 loyalty coupon is used, delete all coupons
            if (count($couponsLp) > 1 || $refreshCoupon) {
                foreach ($couponsLp as $code) {
                    $woocommerce->cart->remove_coupon($code);

                    $coupon = new WC_Coupon($code);

                    $coupon->delete(true);
                }

                $woocommerce->cart->calculate_totals();
            }

            $validator = new WC_Retailcrm_Loyalty_Validator($this->apiClient, $this->settings['corporate_enabled'] ?? WC_Retailcrm_Base::NO);

            if (!$validator->checkAccount($customerId)) {
                return null;
            }

            $lpDiscountSum = $this->getDiscountLp($woocommerce->cart->get_cart(), $site, $customerId);

            if ($lpDiscountSum === 0) {
                return null;
            }

            //Check the existence of loyalty coupons and delete them
            $coupons = $this->getCouponLoyalty($woocommerce->customer->get_email());

            foreach ($coupons as $item) {
                $coupon = new WC_Coupon($item['code']);

                $coupon->delete(true);
            }

            //Generate new coupon
            $coupon = new WC_Coupon();

            //$coupon->set_individual_use(true); // запрещает использование других купонов одноврeменно с этим
            $coupon->set_usage_limit(0);
            $coupon->set_amount($lpDiscountSum);
            $coupon->set_email_restrictions($woocommerce->customer->get_email());
            $coupon->set_code('pl' . mt_rand());
            $coupon->save();

            if ($refreshCoupon) {
                $woocommerce->cart->apply_coupon($coupon->get_code());

                return $resultString;
            }

            $loyaltyInfo = $this->getLoyaltyAccounts($customerId);

            if (!isset($loyaltyInfo['loyaltyAccounts'][0])) {
                return null;
            }

            if ($loyaltyInfo['loyaltyAccounts'][0]['level']['type'] === 'discount') {
                $resultString .= '<div style="background: #05ff13;">' . 'Предоставляется скидка в ' . $lpDiscountSum . $loyaltyInfo['loyaltyAccounts'][0]['loyalty']['currency'] . '</div>';
            } else {
                $resultString .= '<div style="background: #05ff13;">' . 'Возможно списать ' . $lpDiscountSum . ' бонусов' . '</div>';
            }

            return $resultString . '<div style="background: #05ff13;">' . 'Your coupon: ' . $coupon->get_code() . '</div>';
        }

        public function deleteAppliedLoyaltyCoupon()
        {
            global $woocommerce;

            foreach ($woocommerce->cart->get_coupons() as $code => $coupon) {
                if (preg_match('/^pl\d+$/m', $code) === 1) {
                    $woocommerce->cart->remove_coupon($code);

                    $coupon = new WC_Coupon($code);

                    $coupon->delete(true);
                }
            }
        }

        public function getCouponLoyalty($email)
        {
            global $wpdb;

            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT posts.post_name code FROM {$wpdb->prefix}posts AS posts
                            LEFT JOIN {$wpdb->prefix}postmeta AS postmeta ON posts.ID = postmeta.post_id
                            WHERE posts.post_type = 'shop_coupon' AND posts.post_name LIKE 'pl%'
                            AND postmeta.meta_key = 'customer_email' AND postmeta.meta_value LIKE %s",
                    '%' . $email . '%'
                ), ARRAY_A
            );
        }
    }

endif;
