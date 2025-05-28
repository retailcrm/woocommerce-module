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

        public function getInfoLoyalty(array $loyaltyAccount, $history)
        {
            $data = [
                '<b>' . __('Bonus account', 'retailcrm') . '</b>',
                __('Participation ID: ', 'retailcrm') . $loyaltyAccount['id'],
                __('Current level: ', 'retailcrm') . $loyaltyAccount['level']['name'],
                __('Bonuses on the account: ', 'retailcrm') . $loyaltyAccount['amount'],
                __('Bonus card number: ' , 'retailcrm') . ($loyaltyAccount['cardNumber'] ?? __('The card is not linked', 'retailcrm')),
                __('Date of registration: ', 'retailcrm') . $loyaltyAccount['activatedAt'],
                '<hr>',
                '<br>',
                '<b>' . __('Current level rules', 'retailcrm') . '</b>',
                __('Required amount of purchases to move to the next level: ', 'retailcrm') . $loyaltyAccount['nextLevelSum'] . ' ' . $loyaltyAccount['loyalty']['currency'],
            ];

            switch ($loyaltyAccount['level']['type']) {
                case 'bonus_converting':
                    $data[] = sprintf(__('Ordinary products: accrual of 1 bonus for each %s %s', 'retailcrm'), $loyaltyAccount['level']['privilegeSize'], $loyaltyAccount['loyalty']['currency']);
                    $data[] = sprintf(__('Promotional products: accrual of 1 bonus for each %s %s', 'retailcrm'), $loyaltyAccount['level']['privilegeSizePromo'], $loyaltyAccount['loyalty']['currency']);
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

            $htmlTable = '
                <table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 100%; font-family: Arial, sans-serif;">
                <thead>
                    <tr style="background-color: #f2f2f2;">
                       <th style="text-align: left;">Количество</th>
                       <th style="text-align: left;">Дата начисления</th>
                       <th style="text-align: left;">Заказ</th>
                   </tr>
                </thead>
                <tbody>';
                
            foreach ($history->bonusOperations as $node) {
                $amount = isset($node['amount']) ? htmlspecialchars($node['amount']) : '0.00';
                $dateCreate = isset($node['createdAt']) ? htmlspecialchars($node['createdAt']) : 'Нет данных';
                $dateActivation = isset($node['order']['externalId']) ? htmlspecialchars($node['order']['externalId']) : 'Нет данных';
    
                $htmlTable .= "
                <tr>
                     <td style=\"text-align: center;\">$amount</td>
                     <td>$dateCreate</td>
                     <td>$dateActivation</td>
                </tr>";
            } 

            $htmlTable .= '</tbody></table>';

            $result = '';

            foreach ($data as $line) {
                $result .= "<p style='line-height: 1'>$line</p>";
            }

            return $result . $htmlTable;
        }
    }


endif;