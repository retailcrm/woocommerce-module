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

        protected $validator;

        public function __construct($apiClient, $settings)
        {
            $this->apiClient = $apiClient;
            $this->settings = $settings;
            $this->dateFormat = 'Y-m-d H:i:sP';
            $this->loyaltyForm = new WC_Retailcrm_Loyalty_Form();
            $this->validator = new WC_Retailcrm_Loyalty_Validator(
                $this->apiClient,
                isCorporateUserActivate($this->settings)
            );
        }

        public function getForm(int $userId, $loyaltyTerms = '', $loyaltyPersonal = '')
        {
            $result = [];
            $phone = '';

            $wcCustomer = new WC_Customer($userId);

            if ($wcCustomer instanceof WC_Customer) {
                $phone = $wcCustomer->get_billing_phone();
            }

            try {
                $response = $this->getLoyaltyAccounts($userId);
            } catch (Throwable $exception) {
                WC_Retailcrm_Logger::exception(
                    __METHOD__,
                    $exception,
                    'Exception get loyalty accounts: '
                );

                return $result;
            }

            $loyaltyAccount = $response['loyaltyAccounts'][0] ?? null;

            if ($loyaltyAccount) {
                $loyaltyAccount['history'] = $this->getLoyaltyHistory($loyaltyAccount['id']);
                $loyaltyAccount['activationBonuses'] = $this->getBonusDetails($loyaltyAccount['id'], 'waiting_activation');
                $loyaltyAccount['burnBonuses'] = $this->getBonusDetails($loyaltyAccount['id'], 'burn_soon');
            }

            if ($loyaltyAccount && (int) $loyaltyAccount['customer']['externalId'] === $userId) {
                if ($loyaltyAccount['active'] === true) {
                    $result['form'] = $this->loyaltyForm->getInfoLoyalty($loyaltyAccount);
                } else {
                    $result['form'] = $this->loyaltyForm->getActivationForm();

                    $result['loyaltyId'] = $loyaltyAccount['id'];
                }
            } else {
                $result['form'] = $this->loyaltyForm->getRegistrationForm($phone, $loyaltyTerms, $loyaltyPersonal);
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
                $wcCustomer = new WC_Customer($userId);

                if ($wcCustomer instanceof WC_Customer) {
                    $currentPhone = $wcCustomer->get_billing_phone();

                    if (empty($currentPhone) && $phone !== '') {
                        $wcCustomer->set_billing_phone($phone);
                        $wcCustomer->save();
                    }
                }

                $response = $this->apiClient->createLoyaltyAccount($parameters, $site);

                if (!$response->isSuccessful()) {
                    WC_Retailcrm_Logger::error(
                        __METHOD__,
                        'Error while registering in the loyalty program',
                        ['response' => json_decode($response->getRawResponse(), true)]
                    );
                }

                return $response->isSuccessful();
            } catch (Throwable $exception) {
                WC_Retailcrm_Logger::exception(
                    __METHOD__,
                    $exception,
                    'Exception while registering in the loyalty program: '
                );

                return false;
            }
        }

        public function activateLoyaltyCustomer(int $loyaltyId)
        {
            try {
                $response = $this->apiClient->activateLoyaltyAccount($loyaltyId);

                if (!$response->isSuccessful()) {
                    WC_Retailcrm_Logger::error(
                        __METHOD__,
                        'Error while registering in the loyalty program',
                        ['response' => json_decode($response->getRawResponse(), true)]
                    );
                }

                return $response->isSuccessful();
            } catch (Throwable $exception) {
                WC_Retailcrm_Logger::exception(__METHOD__, $exception);

                return false;
            }
        }

        private function getDiscountLoyalty($cartItems, $site, $customerId)
        {
            $discount = 0;
            $chargeRate = 1;
            $response = $this->calculateDiscountLoyalty($cartItems, $site, $customerId);

            if ($response === 0) {
                return $discount;
            }

            // Checking if the loyalty discount is a percent discount
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

            // If the discount has already been given, do not work with points deduction
            if ($discount === 0) {
                foreach ($response['calculations'] as $calculate) {
                    if ($calculate['privilegeType'] !== 'loyalty_level') {
                        continue;
                    }

                    $discount = $calculate['maxChargeBonuses'];
                }

                // Setting 'Bonus exchange rate' from loyalty program in CRM
                $chargeRate = $response['loyalty']['chargeRate'] ?? 1;
            }

            return [$discount * $chargeRate, $chargeRate];
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
                if ($this->isLoyaltyCoupon($code)) {
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

            if (!$this->validator->checkAccount($customerId)) {
                return null;
            }

            [$lpDiscountSum, $lpChargeRate] = $this->getDiscountLoyalty($woocommerce->cart->get_cart(), $site, $customerId);

            if ($lpDiscountSum === 0) {
                return null;
            }

            //Check the existence of loyalty coupons and delete them
            $coupons = $this->getCouponLoyalty($woocommerce->customer->get_email());

            foreach ($coupons as $item) {
                $coupon = new WC_Coupon($item['code']);

                $coupon->delete(true);
            }

            // Generate new coupon
            $coupon = new WC_Coupon();

            $coupon->set_usage_limit(0);
            $coupon->set_amount($lpDiscountSum);
            $coupon->set_email_restrictions($woocommerce->customer->get_email());
            $coupon->set_code('loyalty' . mt_rand());
            $coupon->update_meta_data('chargeRate', $lpChargeRate);
            $coupon->save();

            if ($refreshCoupon) {
                $woocommerce->cart->apply_coupon($coupon->get_code());

                return $resultString;
            }

            //If a percentage discount, automatically apply a loyalty coupon
            if ($this->validator->loyaltyAccount['level']['type'] === 'discount') {
                $woocommerce->cart->apply_coupon($coupon->get_code());

                return $resultString;
            }

            $resultString .= ' <div style="text-align: left; line-height: 3"><b>' . __('It is possible to write off', 'retailcrm') . ' ' . $lpDiscountSum / $lpChargeRate . ' ' . __('bonuses', 'retailcrm') . '</b></div>';
            return $resultString. '<div style="text-align: left;"><b>' . __('Use coupon:', 'retailcrm') . ' <u><i style="cursor: grab" id="input_loyalty_code" onclick="inputLoyaltyCode()">' . $coupon->get_code() . '</i></u></i></b></div>';
        }

        public function clearLoyaltyCoupon()
        {
            global $woocommerce;

            foreach ($woocommerce->cart->get_coupons() as $code => $coupon) {
                if ($this->isLoyaltyCoupon($code)) {
                    $woocommerce->cart->remove_coupon($code);

                    $coupon = new WC_Coupon($code);

                    $coupon->delete(true);
                }
            }
        }

        public function deleteLoyaltyCoupon($couponCode)
        {
            if ($this->isLoyaltyCoupon($couponCode)) {
                $coupon = new WC_Coupon($couponCode);

                $coupon->delete(true);

                return true;
            }

            return  false;
        }

        public function isLoyaltyCoupon($couponCode): bool
        {
            return preg_match('/^loyalty\d+$/m', $couponCode) === 1;
        }

        public function getCouponLoyalty($email)
        {
            global $wpdb;

            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT posts.post_name code FROM {$wpdb->prefix}posts AS posts
                            LEFT JOIN {$wpdb->prefix}postmeta AS postmeta ON posts.ID = postmeta.post_id
                            WHERE posts.post_type = 'shop_coupon' AND posts.post_name LIKE 'loyalty%'
                            AND postmeta.meta_key = 'customer_email' AND postmeta.meta_value LIKE %s",
                    '%' . $email . '%'
                ), ARRAY_A
            );
        }

        public function deleteLoyaltyCouponInOrder($wcOrder)
        {
            $discountLp = 0;
            $chargeRate = 1;
            $coupons = $wcOrder->get_coupons();

            foreach ($coupons as $coupon) {
                $code = $coupon->get_code();

                if ($this->isLoyaltyCoupon($code)) {
                    $discountLp = $coupon->get_discount();

                    $wcOrder->remove_coupon($code);
                    $objectCoupon = new WC_Coupon($code);

                    if (!empty($objectCoupon->get_meta('chargeRate'))) {
                        $chargeRate = (float) $objectCoupon->get_meta('chargeRate');
                    }

                    $objectCoupon->delete(true);
                    $wcOrder->recalculate_coupons();

                    break;
                }
            }

            return [$discountLp, $chargeRate];
        }

        public function isValidOrder($wcCustomer, $wcOrder)
        {
            return !(!$wcCustomer || (isCorporateUserActivate($this->settings) && isCorporateOrder($wcCustomer, $wcOrder)));
        }

        public function isValidUser($wcCustomer)
        {
            if (empty($wcCustomer)) {
                return false;
            }

            try {
                $response = $this->getLoyaltyAccounts($wcCustomer->get_id());
            } catch (Throwable $exception) {
                WC_Retailcrm_Logger::exception(
                    __METHOD__,
                    $exception,
                    'Exception get loyalty accounts: '
                );

                return false;
            }

            return isset($response['loyaltyAccounts'][0]);
        }

        public function applyLoyaltyDiscount($wcOrder, $createdOrder, $discountLp = 0)
        {
            $isPercentDiscount = false;
            $items = [];

            // Verification of automatic creation of the percentage discount of the loyalty program
            foreach ($createdOrder['items'] as $item) {
                foreach ($item['discounts'] as $discount) {
                    if ($discount['type'] === 'loyalty_level') {
                        $isPercentDiscount = true;
                        $items = $createdOrder['items'];

                        break 2;
                    }
                }
            }

            if (!$isPercentDiscount) {
                $response = $this->apiClient->applyBonusToOrder($createdOrder['site'], ['externalId' => $createdOrder['externalId']], (float) $discountLp);

                if (!$response instanceof WC_Retailcrm_Response || !$response->isSuccessful()) {
                    return $response->getErrorString();
                }

                $items = $response['order']['items'];
            }

            $this->calculateLoyaltyDiscount($wcOrder, $items);
        }

        public function calculateLoyaltyDiscount($wcOrder, $orderItems)
        {
            $wcItems = $wcOrder->get_items();

            foreach ($orderItems as $item) {
                $externalId = $item['externalIds'][0]['value'];
                $externalId = preg_replace('/^\d+\_/m', '', $externalId);

                if (isset($wcItems[(int) $externalId])) {
                    $discountLoyaltyTotal = 0;

                    foreach ($item['discounts'] as $discount) {
                        if (in_array($discount['type'], ['bonus_charge', 'loyalty_level'])) {
                            $discountLoyaltyTotal += $discount['amount'];
                        }
                    }

                    $wcItem = $wcItems[(int) $externalId];
                    $wcItem->set_total($wcItem->get_total() - $discountLoyaltyTotal);
                    $wcItem->calculate_taxes();
                    $wcItem->save();
                }
            }

            $wcOrder->calculate_totals();
        }

        public function getCrmItemsInfo($orderExternalId)
        {
            $discountType = null;
            $crmItems = [];

            $response = $this->apiClient->ordersGet($orderExternalId);

            if (!$response instanceof WC_Retailcrm_Response || !$response->isSuccessful() || !isset($response['order'])) {
                WC_Retailcrm_Logger::error(
                    __METHOD__,
                    'Process order: Error when receiving an order from the CRM. Order Id: ' . $orderExternalId
                );

                return [];
            }

            foreach ($response['order']['items'] as $item) {
                $externalId = $item['externalIds'][0]['value'];
                $externalId = preg_replace('/^\d+\_/m', '', $externalId);
                $crmItems[$externalId] = $item;

                if (!$discountType) {
                    foreach ($item['discounts'] as $discount) {
                        if (in_array($discount['type'], ['bonus_charge', 'loyalty_level'])) {
                            $discountType = $discount['type'];
                            break;
                        }
                    }
                }
            }

            return ['items' => $crmItems, 'discountType' => $discountType];
        }

        public function calculateDiscountLoyalty($cartItems, $site, $customerId, $bonuses = 0)
        {
            $order = [
                'site' => $site,
                'customer' => ['externalId' => $customerId],
                'privilegeType' => 'loyalty_level'
            ];

            $useXmlId = isset($this->settings['bind_by_sku']) && $this->settings['bind_by_sku'] === WC_Retailcrm_Base::YES;

            foreach ($cartItems as $item) {
                $product = $item['data'];

                $productRegularPrice = wc_get_price_including_tax($product, ["price" => $product->get_regular_price()]);
                $discount = $productRegularPrice - ($item['line_total'] / $item['quantity']);

                $order['items'][] = [
                    'offer' => $useXmlId ? ['xmlId' => $product->get_sku()] : ['externalId' => $product->get_id()],
                    'quantity' => $item['quantity'],
                    'initialPrice' => $productRegularPrice,
                    'discountManualAmount' => $discount
                ];
            }

            $response = $this->apiClient->calculateDiscountLoyalty($site, $order, (float) $bonuses);

            if (!$response->isSuccessful() || !isset($response['calculations'])) {
                return 0;
            }

            return $response;
        }

        public function getCreditBonuses(): string
        {
            global $woocommerce;

            $customerId = $woocommerce->customer ? $woocommerce->customer->get_id() : null;
            $site = $this->apiClient->getSingleSiteForKey();

            if (
                !$customerId
                || !$woocommerce->cart
                || !$woocommerce->cart->get_cart()
                || !$site
                || !$this->validator->checkAccount($customerId)
            ) {
                return '';
            }

            $loyaltyCoupon = null;
            $coupons = $woocommerce->cart->get_applied_coupons();

            foreach ($coupons as $coupon) {
                if ($this->isLoyaltyCoupon($coupon)) {
                    $loyaltyCoupon = new WC_Coupon($coupon);

                    $woocommerce->cart->remove_coupon($coupon);
                    $woocommerce->cart->calculate_totals();

                    break;
                }
            }

            if ($loyaltyCoupon) {
                $chargeBonuses = $loyaltyCoupon->get_amount();
            }

            $cartItems = $woocommerce->cart->get_cart();
            $response = $this->calculateDiscountLoyalty($cartItems, $site, $customerId, $chargeBonuses ?? 0);

            if ($loyaltyCoupon) {
                $coupon = new WC_Coupon();
                $coupon->set_usage_limit(0);
                $coupon->set_amount($loyaltyCoupon->get_amount());
                $coupon->set_email_restrictions($loyaltyCoupon->get_email_restrictions());
                $coupon->set_code($loyaltyCoupon->get_code());

                $chargeRate = $loyaltyCoupon->get_meta('chargeRate');

                if (!empty($chargeRate)) {
                    $coupon->update_meta_data('chargeRate', $chargeRate);
                }

                $coupon->save();

                $woocommerce->cart->apply_coupon($coupon->get_code());
                $woocommerce->cart->calculate_totals();
            }

            if ($response === 0) {
                return '';
            }

            $creditBonuses = $response['order']['bonusesCreditTotal'];

            if ($creditBonuses) {
                return $this->getHtmlCreditBonuses($creditBonuses);
            }

            return '';
        }

        private function getHtmlCreditBonuses($creditBonuses)
        {
            return '<b style="font-size: large">' . __("Points will be awarded upon completion of the order:", 'retailcrm') . ' <u style="color: green"><i>' . $creditBonuses . '</u></i></b>';
        }

        public function getLoyaltyHistory(int $loyaltyId)
        {
            try {
                $response = $this->apiClient->getClientBonusHistory($loyaltyId, [] , 20);

                return $response['bonusOperations'];
            } catch (Throwable $exception) {
                WC_Retailcrm_Logger::exception(__METHOD__, $exception);

                return [];
            }
        }

        public function getBonusDetails($loyaltyId, $status)
        {
            try {
                $response = $this->apiClient->getDetailClientBonus($loyaltyId, $status, [], 20);

                return $response['bonuses'];
            } catch (Throwable $exception) {
                WC_Retailcrm_Logger::exception(__METHOD__, $exception);

                return [];
            }
        }
    }

endif;
