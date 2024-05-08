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

        protected $isActiveCorp;

        public $crmUser;

        public $loyaltyAccount;

        public function __construct($apiClient, $isActiveCorp)
        {
            $this->apiClient = $apiClient;
            $this->isActiveCorp = $isActiveCorp;
        }

        public function checkAccount(int $userId): bool
        {
            try {
                $this->checkUser($userId);
                $this->checkLoyaltyAccount($this->crmUser['id']);
                $this->checkActiveLoyalty($this->loyaltyAccount['loyalty']['id']);

                return true;
            } catch (ValidatorException $exception) {
                WC_Admin_Settings::add_error((esc_html__($exception->getMessage(), 'retailcrm')) . "userId: $userId");
            } catch (Throwable $exception) {
                WC_Admin_Settings::add_error($exception->getMessage());
            }

            return false;
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

            if ($this->isActiveCorp === WC_Retailcrm_Base::YES && !empty($customer->get_shipping_company())) {
                throw new ValidatorException($this->isCorporateUser, 400);
            }

            $this->crmUser = $responseUser['customer'];
        }

        /**
         * @throws ValidatorException
         */
        private function checkLoyaltyAccount($crmUserId)
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

            $this->loyaltyAccount = $actualAccount;
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
