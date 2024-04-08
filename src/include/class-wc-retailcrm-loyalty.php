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
                    $result = '
                        <p>Бонусный счёт</p>
                        <p>ID участия: TEST</p>
                        <p>Статус участия: ТЕСТ</p>
                        <p>Текущий уровень: ТЕСТ</p>
                        <p>И так далее</p>
                    ';
                } else {
                    $result = '
                    <form>
                        <input type="checkbox" id="loyaltyCheckbox" name="loyaltyCheckbox">
                        <label for="loyaltyCheckbox">Активировать участие в программе лояльности</label>
                        <br>
                        <input type="submit" value="Отправить">
                    </form>
                    ';
                }
            } else {
                $result = '
                <form action="register.php" method="post">
                    <p>Для регистрации в Программе лояльности заполните форму:</p>
                    <p><input type="checkbox" name="terms" id="terms"> Я согласен с <a href="terms.html">условиями программы лояльности</a>.</p>
                    <p><input type="checkbox" name="privacy" id="privacy"> Я согласен с <a href="privacy.html">условиями обработки персональных данных</a>.</p>
                    <p><input type="text" name="phone" id="phone" placeholder="Телефон"></p>
                    <p><input type="submit" value="Отправить"></p>
                </form>
                ';
            }

           return $result;
        }
    }

endif;
