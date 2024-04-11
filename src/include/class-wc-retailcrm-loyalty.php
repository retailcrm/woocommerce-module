<?php

if (!class_exists('WC_Retailcrm_Loyalty')) :
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

        public function __construct($apiClient, $settings)
        {
            $this->apiClient = $apiClient;
            $this->settings = $settings;
            $this->dateFormat = 'Y-m-d H:i:sP';
        }

        public function getForm(int $userId)
        {
            $result = [];

            try {
                $response = $this->apiClient->customersGet($userId);

                if (!isset($response['customer']['id'])) {
                    return $result;
                }

                $filter['customerId'] = $response['customer']['id'];

                $response = $this->apiClient->getLoyaltyAccountList($filter);
            } catch (Throwable $exception) {
                writeBaseLogs('Exception get loyalty accounts: ' . $exception->getMessage());

                return $result;
            }

            if (!$response->isSuccessful() || !$response->offsetExists('loyaltyAccounts')) {
                return $result;
            }

            $loyaltyAccount = $response['loyaltyAccounts'][0] ?? null;

            if ($loyaltyAccount && (int) $loyaltyAccount['customer']['externalId'] === $userId) {
                if ($loyaltyAccount['active'] === true) {
                    $result['form'] = $this->getLoyaltyInfo($loyaltyAccount);
                } else {
                    $result['form'] = sprintf(
                        '
                        <form id="loyaltyActivateForm" method="post">
                            <p><input type="checkbox" id="loyaltyActiveCheckbox" name="loyaltyCheckbox" required> %s</p>
                            <input type="submit" value="%s">
                        </form>',
                        __('Activate participation in the loyalty program', 'retailcrm'),
                        __('Send', 'retailcrm')
                    );

                    $result['loyaltyId'] = $loyaltyAccount['id'];
                }
            } else {
                $result['form'] = sprintf(
                    '
                    <form id="loyaltyRegisterForm" method="post">
                        <p>%s</p>
                        <p><input type="checkbox" name="terms" id="termsLoyalty" required>%s<a id="terms-popup" class="popup-open-loyalty" href="#">%s</a>.</p>
                        <p><input type="checkbox" name="privacy" id="privacyLoyalty" required>%s<a id="privacy-popup" class="popup-open-loyalty" href="#">%s</a>.</p>
                        <p><input type="text" name="phone" id="phoneLoyalty" placeholder="%s" required></p>
                        <p><input type="submit" value="%s"></p>
                    </form>
                    <div class="popup-fade-loyalty">
                        <div class="popup-loyalty">
                            <a class="popup-close-loyalty" href="#">%s</a>
                            <br>
                            <div id="popup-loyalty-text"></div>
                        </div>		
                    </div>
                    ',
                    __('To register in the loyalty program, fill in the form:', 'retailcrm'),
                    __(' I agree with ', 'retailcrm'),
                    __('loyalty program terms', 'retailcrm'),
                    __(' I agree with ', 'retailcrm'),
                    __('terms of personal data processing', 'retailcrm'),
                    __('Phone', 'retailcrm'),
                    __('Send', 'retailcrm'),
                    __('Close', 'retailcrm')
                );
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

        private function getLoyaltyInfo(array $loyaltyAccount)
        {
            $data = [
                '<b>' . __('Bonus account', 'retailcrm') . '</b>',
                __('Participation ID', 'retailcrm') . ': ' . $loyaltyAccount['id'],
                __('Current level', 'retailcrm') . ': ' . $loyaltyAccount['level']['name'],
                __('Bonuses on the account', 'retailcrm') . ': ' . $loyaltyAccount['amount'],
                __('Bonus card number' , 'retailcrm') . ': ' . ($loyaltyAccount['cardNumber'] ?? __('The card is not linked', 'retailcrm')),
                __('Date of registration', 'retailcrm') . ': ' . $loyaltyAccount['activatedAt'],
                '<br>',
                '<b>' . __('Current level rules', 'retailcrm') . '</b>',
                __('Required amount of purchases to move to the next level', 'retailcrm') . ': ' . $loyaltyAccount['nextLevelSum'],
            ];

            switch ($loyaltyAccount['level']['type']) {
                case 'bonus_converting':
                    $data[] = __('Ordinary goods', 'retailcrm') . ': ' . __('accrual of 1 bonus for each', 'retailcrm') . ' '. $loyaltyAccount['level']['privilegeSize'] . ' ' . $loyaltyAccount['loyalty']['currency'];
                    $data[] = __('Promotional products', 'retailcrm') . ': ' . __('accrual of 1 bonus for each', 'retailcrm') . ' '. $loyaltyAccount['level']['privilegeSizePromo']. ' ' . $loyaltyAccount['loyalty']['currency'];
                    break;
                case 'bonus_percent':
                    $data[] = __('Ordinary goods', 'retailcrm') . ': ' . __('bonus accrual in the amount of', 'retailcrm'). ' ' . $loyaltyAccount['level']['privilegeSize'] . '% ' . __('of the purchase amount', 'retailcrm');
                    $data[] = __('Promotional products', 'retailcrm') . ': ' . __('bonus accrual in the amount of', 'retailcrm'). ' ' . $loyaltyAccount['level']['privilegeSizePromo'] . '% ' . __('of the purchase amount', 'retailcrm');
                    break;
                case 'discount':
                    $data[] = __('Ordinary goods', 'retailcrm') . ': ' . $loyaltyAccount['level']['privilegeSize'] . '% ' . __('discount', 'retailcrm');
                    $data[] = __('Promotional products', 'retailcrm') . ': ' . $loyaltyAccount['level']['privilegeSizePromo'] . '% ' . __('discount', 'retailcrm');
                    break;
            }

            $result = '';

            foreach ($data as $line) {
                $result .= "<p style='line-height: 1'>$line</p>";
            }

            return $result;
        }
    }

endif;
