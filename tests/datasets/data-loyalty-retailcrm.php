<?php

namespace datasets;

/**
 * PHP version 7.0
 *
 * Class DataLoyaltyRetailCrm - Data set for WC_Retailcrm_Loyalty_Test.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */

class DataLoyaltyRetailCrm
{
    public static function getDataLoyalty()
    {
        return [
            [
                'isSuccessful' => true,
                'body' => json_encode(['loyaltyAccounts' => []]),
                'expected' => 'id="loyaltyRegisterForm"'
            ],
            [
                'isSuccessful' => true,
                'body' => json_encode(
                    [
                        'loyaltyAccounts' => [
                            0 => [
                                'active' => false,
                                'customer' => [
                                    'externalId' => 1
                                ],
                                'id' => 1
                            ]
                        ]
                    ]
                ),
                'expected' => 'id="loyaltyActivateForm"'
            ],
            [
                'isSuccessful' => true,
                'body' => json_encode(
                    [
                        'loyaltyAccounts' => [
                            0 => [
                                'id' => 1,
                                'level' => [
                                    'name' => 'Test level',
                                    'privilegeSize' => 5,
                                    'privilegeSizePromo' => 3,
                                    'type' => 'bonus_converting'
                                ],
                                'amount' => 1000,
                                'cardNumber' => '12345',
                                'activatedAt' => '2024-04-10 15:00:00',
                                'nextLevelSum' => 15000,
                                'loyalty' => [
                                    'currency' => 'USD'
                                ],
                                'customer' => [
                                    'externalId' => 1
                                ],
                                'active' => true
                            ]
                        ]
                    ]
                ),
                'expected' => 'Ordinary products: accrual of 1 bonus for each'
            ],
        ];
    }
}
