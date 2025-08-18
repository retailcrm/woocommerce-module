<?php

if (!class_exists('WC_Retailcrm_Loyalty')) :


    class WC_Retailcrm_Loyalty_Form
    {
        public function getRegistrationForm($phone = '', $loyaltyTerms = '', $loyaltyPersonal = '')
        {
            $htmlLoyaltyTerms = $loyaltyTerms !== ''
                ? sprintf(
                    '<p><input type="checkbox" name="terms" id="termsLoyalty" required>%s<a id="terms-popup" class="popup-open-loyalty" href="#">%s</a>.</p>',
                    esc_html__(' I agree with ', 'woo-retailcrm'),
                    esc_html__('loyalty program terms', 'woo-retailcrm')
                )
                : ''
            ;

            $htmlLoyaltyPersonal = $loyaltyPersonal !== ''
                ? sprintf(
                '<p><input type="checkbox" name="privacy" id="privacyLoyalty" required>%s<a id="privacy-popup" class="popup-open-loyalty" href="#">%s</a>.</p>',
                esc_html__(' I agree with ', 'woo-retailcrm'),
                esc_html__('terms of personal data processing', 'woo-retailcrm')
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
                esc_html__('To register in the loyalty program, fill in the form:', 'woo-retailcrm'),
                $htmlLoyaltyTerms,
                $htmlLoyaltyPersonal,
                esc_html__('Phone', 'woo-retailcrm'),
                $phone,
                esc_html__('Send', 'woo-retailcrm'),
                esc_html__('Close', 'woo-retailcrm')
            );
        }

        public function getActivationForm()
        {
            return sprintf('
                    <form id="loyaltyActivateForm" method="post">
                        <p><input type="checkbox" id="loyaltyActiveCheckbox" name="loyaltyCheckbox" required> %s</p>
                        <input type="submit" value="%s">
                    </form>',
                esc_html__('Activate participation in the loyalty program', 'woo-retailcrm'),
                esc_html__('Send', 'woo-retailcrm')
            );
        }

        public function getInfoLoyalty(array $loyaltyAccount)
        {
            $operationTypes = [
                    'credit_manual' => esc_html__('Сredited by manager', 'woo-retailcrm'),
                    'charge_manual' => esc_html__('Сharged by manager', 'woo-retailcrm'),
                    'credit_for_order' => esc_html__('Сredited for order ', 'woo-retailcrm'),
                    'burn' => esc_html__('Burn','woo-retailcrm'),
                    'charge_for_order' => esc_html__('Сharged for order ', 'woo-retailcrm'),
                    'credit_for_event' => esc_html__('Credited for event', 'woo-retailcrm'),
                    'cancel_of_charge' => esc_html__('Сancel of charge for order ', 'woo-retailcrm'),
                    'cancel_of_credit' => esc_html__('Сancel of credit for order ', 'woo-retailcrm'),
            ];
            $currency = ' ' . $loyaltyAccount['loyalty']['currency'];
            $burnInfo = $loyaltyAccount['burnBonuses'][0] ?? [];
            $activationInfo = $loyaltyAccount['activationBonuses'][0] ?? [];

            switch ($loyaltyAccount['level']['type']) {
                case 'bonus_converting':
                    $ordinaryRule = sprintf('<p style="color:gray">' . esc_html__('Ordinary products: accrual of 1 bonus for each %s %s', 'woo-retailcrm'), esc_html($loyaltyAccount['level']['privilegeSize']), esc_html($currency));
                    $promotionRule = sprintf('<p style="color:gray">' . esc_html__('Promotional products: accrual of 1 bonus for each %s %s', 'woo-retailcrm'),  esc_html($loyaltyAccount['level']['privilegeSizePromo']),  esc_html($currency));
                    break;
                case 'bonus_percent':
                    $ordinaryRule  = sprintf('<p style="color:gray">' . esc_html__('Ordinary products: bonus accrual in the amount of %s%% of the purchase amount', 'woo-retailcrm'), esc_html($loyaltyAccount['level']['privilegeSize']));
                    $promotionRule = sprintf('<p style="color:gray">' . esc_html__('Promotional products: bonus accrual in the amount of %s%% of the purchase amount', 'woo-retailcrm'), esc_html($loyaltyAccount['level']['privilegeSizePromo']));
                    break;
                case 'discount':
                    $ordinaryRule  = sprintf('<p style="color:gray">' . esc_html__('Ordinary products: %s%% discount', 'woo-retailcrm'), $loyaltyAccount['level']['privilegeSize']);
                    $promotionRule = sprintf('<p style="color:gray">' . esc_html__('Promotional products: %s%% discount', 'woo-retailcrm'), $loyaltyAccount['level']['privilegeSizePromo']);
                    break;
            }

            
            $data = [
                    '<b style="font-size: 150%">' . esc_html__('Bonuses and discount', 'woo-retailcrm') . '</b>',
                    $loyaltyAccount['level']['type'] !== 'discount' ? '<b>' . sprintf(esc_html__('You have %s bonuses', 'woo-retailcrm'), esc_html($loyaltyAccount['amount'])) . '</b>' : '',
                    $burnInfo !== [] && $loyaltyAccount['level']['type'] !== 'discount' ? sprintf('<p style="color:gray">' . esc_html__('%s bonuses will expire %s', 'woo-retailcrm'), esc_html($burnInfo['amount']), esc_html($burnInfo['date'])) . '</b>' : '',
                    $activationInfo !== [] && $loyaltyAccount['level']['type'] !== 'discount' ? sprintf('<p style="color:gray">' . esc_html__('%s bonuses will active %s', 'woo-retailcrm'), esc_html($activationInfo['amount']), esc_html($activationInfo['date'])) : '',
                    '<b>' . $loyaltyAccount['level']['name'] . '</b>',
                    $ordinaryRule,
                    $promotionRule,
                    '<b>' . esc_html__('Total order summ ', 'woo-retailcrm') . esc_html($loyaltyAccount['ordersSum'] . $currency . '</b>'),
            ];

            if ($loyaltyAccount['nextLevelSum']) {
                $data[] = '<p style="color:gray">' . esc_html__('Total summ for next level: ', 'woo-retailcrm') . esc_html(($loyaltyAccount['nextLevelSum'] - $loyaltyAccount['ordersSum']) . $currency);
            }
            

            $data[] = '<b style="font-size: 100%">' . esc_html__('History', 'woo-retailcrm') . '</b>';

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
