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

    public static function dataCheckUser()
    {
        return [
            [
                'responseApiMethod' => [],
                'wcUserType' => 'individual',
                'throwMessage' => 'User not found in the system',
                'isCorpActive' => false
            ],
            [
                'responseApiMethod' => ['customer' => ['id' => 1]],
                'wcUserType' => 'corp',
                'throwMessage' => 'This user is a corporate person',
                'isCorpActive' => true,
            ],
            [
                'responseApiMethod' => ['customer' => ['id' => 1]],
                'wcUserType' => 'corp',
                'throwMessage' => null,
                'isCorpActive' => false,
            ],
            [
                'responseApiMethod' => ['customer' => ['id' => 1]],
                'wcUserType' => 'individual',
                'throwMessage' => null,
                'isCorpActive' => true,
            ],
        ];
    }

    public static function dataLoyaltyAccount()
    {
        return [
            [
                'responseMock' => ['success' => true],
                'throwMessage' => 'Error when searching for participation in loyalty programs'
            ],
            [
                'responseMock' => ['success' => true, 'loyaltyAccounts' => []],
                'throwMessage' => 'No active participation in the loyalty program was detected'
            ],
            [
                'responseMock' => ['success' => true, 'loyaltyAccounts' => [['active' => true, 'amount' => 0, 'level' => ['type' => 'bonus_converting']]]],
                'throwMessage' => 'No bonuses for debiting'
            ],
            [
                'responseMock' => ['success' => true, 'loyaltyAccounts' => [['active' => true, 'amount' => 0, 'level' => ['type' => 'discount']]]],
                'throwMessage' => null
            ],
            [
                'responseMock' => ['success' => true, 'loyaltyAccounts' => [['active' => true, 'amount' => 100, 'level' => ['type' => 'bonus_converting']]]],
                'throwMessage' => null
            ],
        ];
    }

    public static function dataCheckActiveLoyalty()
    {
        return [
            [
                'responseMock' => ['success' => true],
                'throwMessage' => 'Loyalty program not found'
            ],
            [
                'responseMock' => ['success' => true, 'loyalty' => ['active' => false]],
                'throwMessage' => 'Loyalty program is not active'
            ],
            [
                'responseMock' => ['success' => true, 'loyalty' => ['active' => true, 'blocked' => true]],
                'throwMessage' => 'Loyalty program blocked'
            ],
            [
                'responseMock' => ['success' => true, 'loyalty' => ['active' => true, 'blocked' => false]],
                'throwMessage' => null
            ]
        ];
    }
}
