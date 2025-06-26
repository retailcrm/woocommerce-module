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
            $operationTypes = [
                    'credit_manual' => __('Сredited by manager', 'retailcrm'),
                    'charge_manual' => __('Сharged by manager', 'retailcrm'),
                    'credit_for_order' => __('Сredited for order ', 'retailcrm'),
                    'burn' => __('Burn','retailcrm'),
                    'charge_for_order' => __('Сharged for order ', 'retailcrm'),
                    'credit_for_event' => __('Credited by event', 'retailcrm')
            ];
            $currency = ' ' . $loyaltyAccount['loyalty']['currency'];
            $burnInfo = $loyaltyAccount['burnBonuses'][0] ?: [];
            $activationInfo = $loyaltyAccount['activationBonuses'][0] ?: [];

            switch ($loyaltyAccount['level']['type']) {
                case 'bonus_converting':
                    $ordinaryRule = sprintf('<p style="color:gray">' . __('Ordinary products: accrual of 1 bonus for each %s %s', 'retailcrm'), $loyaltyAccount['level']['privilegeSize'], $currency);
                    $promotionRule = sprintf('<p style="color:gray">' . __('Promotional products: accrual of 1 bonus for each %s %s', 'retailcrm'), $loyaltyAccount['level']['privilegeSizePromo'], $currency);
                    break;
                case 'bonus_percent':
                    $ordinaryRule  = sprintf('<p style="color:gray">' . __('Ordinary products: bonus accrual in the amount of %s%% of the purchase amount', 'retailcrm'), $loyaltyAccount['level']['privilegeSize']);
                    $promotionRule = sprintf('<p style="color:gray">' . __('Promotional products: bonus accrual in the amount of %s%% of the purchase amount', 'retailcrm'), $loyaltyAccount['level']['privilegeSizePromo']);
                    break;
                case 'discount':
                    $ordinaryRule  = sprintf('<p style="color:gray">' . __('Ordinary products: %s%% discount', 'retailcrm'), $loyaltyAccount['level']['privilegeSize']);
                    $promotionRule = sprintf('<p style="color:gray">' . __('Promotional products: %s%% discount', 'retailcrm'), $loyaltyAccount['level']['privilegeSizePromo']);
                    break;
            }

            
            $data = [
                    '<b style="font-size: 150%">' . __('Bonuses and discount', 'retailcrm') . '</b>',
                    $loyaltyAccount['level']['type'] !== 'discount' ? '<b>' . sprintf(__('You have %s bonuses', 'retailcrm'), $loyaltyAccount['amount']) . '</b>' : '',
                    $burnInfo !== [] && $loyaltyAccount['level']['type'] !== 'discount' ? sprintf('<p style="color:gray">' . __('%s bonuses will expire %s', 'retailcrm'), $burnInfo['amount'], $burnInfo['date']) . '</b>' : '',
                    $activationInfo !== [] && $loyaltyAccount['level']['type'] !== 'discount' ? sprintf('<p style="color:gray">' . __('%s bonuses will active %s', 'retailcrm'), $activationInfo['amount'], $activationInfo['date']) : '',
                    '<b>' . $loyaltyAccount['level']['name'] . '</b>',
                    $ordinaryRule,
                    $promotionRule,
                    '<b>' . __('Total order summ ', 'retailcrm') . $loyaltyAccount['ordersSum'] . $currency . '</b>',
            ];

            if ($loyaltyAccount['nextLevelSum']) {
                $data[] = '<p style="color:gray">' . __('Total summ for next level: ', 'retailcrm') . ($loyaltyAccount['nextLevelSum'] - $loyaltyAccount['ordersSum']) . $currency;
            }
            

            $data[] = '<b style="font-size: 100%">' . __('History', 'retailcrm') . '</b>';

            $htmlTable = '
                <table style="width: 75%; border: none;>
                <tbody>';

            foreach ($loyaltyAccount['history'] as $operation) {
                $amount = $operation['amount'];
                $dateCreate = $operation['createdAt'];
                $description = isset($operationTypes[$operation['type']]) ? $operationTypes[$operation['type']] : '-';

                if (isset($operation['order']['externalId'])) {
                    $order = wc_get_order($operation['order']['externalId']);

                    if ($order) {
                        $order_url = $order->get_view_order_url();
                        $link = sprintf('<a href = "%s">%s</a>', $order_url, $operation['order']['externalId'] );
                        $description .= $link;
                    }
                }

                $colorText = $amount < 0 ? 'red' : 'green';
                $symbol = $colorText === 'red' ? '- ' : '+ ';
                $bonusCount = abs($amount);

                $htmlTable .= "
                <tr style=\"background-color:rgb(255, 255, 255);\">
                     <td style=\"text-align: left; font-size:105%; border: none; color: {$colorText}\"><b>$symbol $bonusCount</b></td>
                     <td style=\"text-align: center; color: gray; font-size:105%; border: none;\">$dateCreate</td>
                     <td style=\"text-align: center; font-size:105%; border: none; \">$description</td>
                </tr>";
            } 

            $htmlTable .= '</tbody></table>';

            $result = '';

            foreach ($data as $line) {
                $result .= "<p style='line-height: 1.75'>$line</p>";
            }

            return $result . $htmlTable;
        }
    }

endif;
