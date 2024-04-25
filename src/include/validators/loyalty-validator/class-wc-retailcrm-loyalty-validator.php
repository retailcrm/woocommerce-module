<?php

if (!class_exists('WC_Retailcrm_Loyalty_Validator')) :
    /**
     * PHP version 7.0
     *
     * Class WC_Retailcrm_Loyalty_Validator - Validate CRM loyalty.
     *
     * @category Integration
     * @author   RetailCRM <integration@retailcrm.ru>
     * @license  http://retailcrm.ru Proprietary
     * @link     http://retailcrm.ru
     * @see      http://help.retailcrm.ru
     */
    class WC_Retailcrm_Loyalty_Validator extends WC_Retailcrm_Loyalty_Constraint
    {
        /** @var WC_Retailcrm_Client_V5 */
        protected $apiClient;

        public function __construct($apiClient)
        {
            $this->apiClient = $apiClient;
        }

        public function checkAccount(int $userId)
        {
            $result = false;

            try {
                $crmUser = $this->checkUser($userId);
                $actualAccount = $this->getLoyaltyAccount($crmUser['id']);
                $this->checkActiveLoyalty($actualAccount['loyalty']['id']);
            } catch (Throwable $exception) {
                if ($exception instanceof ValidatorException) {
                    WC_Admin_Settings::add_error((esc_html__($exception->getMessage(), 'retailcrm')) . "userId: $userId");
                } else {
                    WC_Admin_Settings::add_error($exception->getMessage());
                }
            }
        }

        /**
         * @throws ValidatorException
         */
        private function checkUser($userId)
        {
            $responseUser = $this->apiClient->customersGet($userId);

            if (!isset($responseUser['customer']['id'])) {
                throw new ValidatorException($this->notFoundCrmUser, 400);
            }

            $customer = new WC_Customer($userId);

            if (!empty($customer->get_shipping_company())) {
                throw new ValidatorException($this->isCorporateUser, 400);
            }

            return $responseUser['customer'];
        }

        /**
         * @throws ValidatorException
         */
        private function getLoyaltyAccount($crmUserId)
        {
            $filter['customerId'] = $crmUserId;
            $responseLoyalty = $this->apiClient->getLoyaltyAccountList($filter);

            if (!$responseLoyalty->isSuccessful() || !$responseLoyalty->offsetExists('loyaltyAccounts')) {
                throw new ValidatorException($this->errorFoundLoyalty, 400);
            }

            $actualAccount = null;

            foreach ($responseLoyalty['loyaltyAccounts'] as $loyaltyAccount) {
                if ($loyaltyAccount['active'] === true) {
                    $actualAccount = $loyaltyAccount;
                }
            }

            if (!isset($actualAccount)) {
                throw new ValidatorException($this->notFoundActiveParticipation, 400);
            }

            if ($actualAccount['amount'] === 0 && $actualAccount['level']['type'] !== 'discount') {
               throw new ValidatorException($this->notExistBonuses, 400);
            }

            return $actualAccount;
        }

        /**
         * @throws ValidatorException
         */
        private function checkActiveLoyalty($idLoyalty)
        {
            $responseLoyalty = $this->apiClient->getLoyalty($idLoyalty);

            if (!$responseLoyalty->isSuccessful() || !$responseLoyalty->offsetExists('loyalty')) {
                throw new ValidatorException($this->notFoundLoyalty, 400);
            }

            if ($responseLoyalty['loyalty']['active'] !== true) {
                throw new ValidatorException($this->loyaltyInactive, 400);
            }

            if ($responseLoyalty['loyalty']['blocked'] === true) {
                throw new ValidatorException($this->loyaltyBlocked, 400);
            }
        }
    }
endif;
