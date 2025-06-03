<?php

if (!class_exists('WC_Retailcrm_Loyalty')) :


    class WC_Retailcrm_Loyalty_Form
    {
        public function getRegistrationForm($phone = '', $loyaltyTerms = '', $loyaltyPersonal = '')
        {
            $htmlLoyaltyTerms = $loyaltyTerms !== ''
                ? sprintf(
                    '<p><input type="checkbox" name="terms" id="termsLoyalty" required>%s<a id="terms-popup" class="popup-open-loyalty" href="#">%s</a>.</p>',
                    __(' I agree with ', 'retailcrm'),
                    __('loyalty program terms', 'retailcrm')
                )
                : ''
            ;

            $htmlLoyaltyPersonal = $loyaltyPersonal !== ''
                ? sprintf(
                '<p><input type="checkbox" name="privacy" id="privacyLoyalty" required>%s<a id="privacy-popup" class="popup-open-loyalty" href="#">%s</a>.</p>',
                __(' I agree with ', 'retailcrm'),
                __('terms of personal data processing', 'retailcrm')
                )
                : ''
            ;


            return sprintf(
                '
                    <form id="loyaltyRegisterForm" method="post">
                        <p>%s</p>
                        %s
                        %s
                        <p><input type="text" name="phone" id="phoneLoyalty" placeholder="%s" value="%s" required></p>
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
                $htmlLoyaltyTerms,
                $htmlLoyaltyPersonal,
                __('Phone', 'retailcrm'),
                $phone,
                __('Send', 'retailcrm'),
                __('Close', 'retailcrm')
            );
        }

        public function getActivationForm()
        {
            return sprintf('
                    <form id="loyaltyActivateForm" method="post">
                        <p><input type="checkbox" id="loyaltyActiveCheckbox" name="loyaltyCheckbox" required> %s</p>
                        <input type="submit" value="%s">
                    </form>',
                __('Activate participation in the loyalty program', 'retailcrm'),
                __('Send', 'retailcrm')
            );
        }

        public function getInfoLoyalty(array $loyaltyAccount)
        {
            $operationTypes = 
                [
                    'credit_manual' => __('Сredited', 'retailcrm'),
                    'charge_manual' => __('Сharged', 'retailcrm'),
                    'credit_for_order' => __('Сredited for order', 'retailcrm'),
                    'burn' => __('Burn','retailcrm'),
                    'charge_for_order' => __('Сharged for order', 'retailcrm'),
                ];

            $currency = ' ' . $loyaltyAccount['loyalty']['currency'];

            $data = 
                [
                    '<b style="font-size: 150%">' . __('Bonuses and discount', 'retailcrm') . '</b>',
                    '<b>' . __('Bonuses on your account: ', 'retailcrm') . '</b>' . $loyaltyAccount['amount'],
                    '<b>' . __('Total order summ: ', 'retailcrm') . '</b>',
                    $loyaltyAccount['ordersSum'] . $currency . ' / ' . __('Total summ for next level: ', 'retailcrm') . $loyaltyAccount['nextLevelSum'] . $currency,
                    '<b>' . $loyaltyAccount['level']['name'] . '</b>',
                ];

            switch ($loyaltyAccount['level']['type']) {
                case 'bonus_converting':
                    $data[] = sprintf(__('Ordinary products: accrual of 1 bonus for each %s %s', 'retailcrm'), $loyaltyAccount['level']['privilegeSize'], $currency);
                    $data[] = sprintf(__('Promotional products: accrual of 1 bonus for each %s %s', 'retailcrm'), $loyaltyAccount['level']['privilegeSizePromo'], $currency);
                    break;
                case 'bonus_percent':
                    $data[] = sprintf(__('Ordinary products: bonus accrual in the amount of %s%% of the purchase amount', 'retailcrm'), $loyaltyAccount['level']['privilegeSize']);
                    $data[] = sprintf(__('Promotional products: bonus accrual in the amount of %s%% of the purchase amount', 'retailcrm'), $loyaltyAccount['level']['privilegeSizePromo']);
                    break;
                case 'discount':
                    $data[] = sprintf(__('Ordinary products: %s%% discount', 'retailcrm'), $loyaltyAccount['level']['privilegeSize']);
                    $data[] = sprintf(__('Promotional products: %s%% discount', 'retailcrm'), $loyaltyAccount['level']['privilegeSizePromo']);
                    break;
            }

            $data[] = '<b style="font-size: 100%">' . __('History', 'retailcrm') . '</b>';

            $htmlTable = '
                <table cellpadding="4" cellspacing="0" style="width: 100%; border: none;>
                <tbody>';

            foreach ($loyaltyAccount['history'] as $node) {
                $amount = $node['amount'];
                $dateCreate = $node['createdAt'];
                $description = isset($operationTypes[$node['type']]) ? $operationTypes[$node['type']] : '-';

                $colorText = $amount < 0 ? 'red' : 'green';

                $htmlTable .= "
                <tr style=\"background-color:rgb(255, 255, 255); font-size:105%\">
                     <td style=\"text-align: center; border: none; color: {$colorText}\">$amount</td>
                     <td style=\"text-align: center; border: none;\">$dateCreate</td>
                     <td style=\"text-align: center; border: none; \">$description</td>
                </tr>";
            } 

            $htmlTable .= '</tbody></table>';

            $result = '';

            foreach ($data as $line) {
                $result .= "<p style='line-height: 1.5'>$line</p>";
            }

            return $result . $htmlTable;
        }
    }

endif;