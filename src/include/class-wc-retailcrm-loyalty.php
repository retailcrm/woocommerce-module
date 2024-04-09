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
            $result = '';

            $response = $this->apiClient->customersGet($userId);

            if (!isset($response['customer']['id'])) {
                return $result;
            }

            $filter['customerId'] = $response['customer']['id'];
            $response = $this->apiClient->getLoyaltyAccountList($filter);

            if (!$response->isSuccessful() || !$response->offsetExists('loyaltyAccounts')) {
                return $result;
            }

            $loyaltyAccount = $response['loyaltyAccounts'][0];

            if (isset($response['loyaltyAccounts'][0]) && (int)$loyaltyAccount['customer']['externalId'] === $userId) {
                if ($loyaltyAccount['active'] === true) {
                    $result = $this->getLoyaltyInfo($loyaltyAccount);
                } else {
                    $result = sprintf(
                        '
                        <form id="loyaltyActivateForm" method="post">
                            <input type="checkbox" id="loyaltyCheckbox" name="loyaltyCheckbox" required>
                            <label for="loyaltyCheckbox">%s</label>
                            <br>
                            <input type="submit" value="%s">
                        </form>',
                        __('Activate participation in the loyalty program', 'retailcrm'),
                        __('Send', 'retailcrm')
                    );
                }
            } else {
                $result = sprintf(
                    '
                    <form id="loyaltyRegisterForm" method="post">
                        <p>%s</p>
                        <p><input type="checkbox" name="terms" id="termsLoyalty" required>%s<a href="terms.html">%s</a>.</p>
                        <p><input type="checkbox" name="privacy" id="privacyLoyalty" required>%s<a href="privacy.html">%s</a>.</p>
                        <p><input type="text" name="phone" id="phoneLoyalty" placeholder="%s" required></p>
                        <p><input type="submit" value="%s"></p>
                    </form>',
                    __('To register in the Loyalty Program, fill in the form:', 'retailcrm'),
                    __(' I agree with ', 'retailcrm'),
                    __('loyalty program terms', 'retailcrm'),
                    __(' I agree with ', 'retailcrm'),
                    __('terms of personal data processing', 'retailcrm'),
                    __('Phone', 'retailcrm'),
                    __('Send', 'retailcrm')
                );
            }

           return $result;
        }

        public function registerCustomer(int $userId, string $phone, string $site): WC_Retailcrm_Response
        {
            $parameters = [
                'phoneNumber' => $phone,
                'customer' => [
                    'externalId' => $userId
                ]
            ];

            return $this->apiClient->createLoyaltyAccount($parameters, $site);
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
                    $data[] = __('Ordinary goods', 'retailcrm') . ': 1 ' . __('bonus for every', 'retailcrm') . ' '. $loyaltyAccount['level']['privilegeSize'];
                    $data[] = __('Promotional products', 'retailcrm') . ': 1 ' . __('bonus for every', 'retailcrm') . ' '. $loyaltyAccount['level']['privilegeSizePromo'];
                    break;
                case 'bonus_percent':
                    $data[] = __('Ordinary goods', 'retailcrm') . ': ' . $loyaltyAccount['level']['privilegeSize'] . '% ' . __('bonuses', 'retailcrm');
                    $data[] = __('Promotional products', 'retailcrm') . ': ' . $loyaltyAccount['level']['privilegeSizePromo'] . '% ' . __('bonuses', 'retailcrm');
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
