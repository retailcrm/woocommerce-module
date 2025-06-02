<?php
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
                                'active' => 1,
                                'phoneNumber' => '+799925452222',
                                'ordersSum' => 100,
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
                'isCorpActive' => true
            ],
            [
                'responseApiMethod' => ['customer' => ['id' => 1]],
                'wcUserType' => 'corp',
                'throwMessage' => null,
                'isCorpActive' => false
            ],
            [
                'responseApiMethod' => ['customer' => ['id' => 1]],
                'wcUserType' => 'individual',
                'throwMessage' => null,
                'isCorpActive' => true
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

    public static function createProducts()
    {
        $product1 = new WC_Product();
        $product1->set_name('Test product 1');
        $product1->set_sku('test1' . mt_rand());
        $product1->set_regular_price('200');
        $product1->set_sale_price('200');
        $product1->save();

        $product2 = new WC_Product();
        $product2->set_name('Test product 2');
        $product2->set_sku('test2' . mt_rand());
        $product2->set_regular_price('100');
        $product2->set_sale_price('50');
        $product2->save();

        return [$product1, $product2];
    }

    public static function getDataCalculation()
    {
        return [
            [
                'response' => [
                    'calculations' => [],
                    'order' => [
                        'items' => [
                            [
                                'discounts' => [
                                    [
                                        'type' => 'loyalty_level',
                                        'amount' => 20
                                    ],
                                ]
                            ],
                            [
                                'discounts' => [
                                    [
                                        'type' => 'loyalty_level',
                                        'amount' => 20
                                    ],
                                ]
                            ]
                        ]
                    ]
                ],
                'expected' => 40
            ],
            [
                'response' => [
                    'calculations' => [
                        [
                            'privilegeType' => 'test'
                        ],
                        [
                            'privilegeType' => 'loyalty_level',
                            'maxChargeBonuses' => 50
                        ]
                    ],
                    'order' => [
                        'items' => [['discounts' => null], ['discounts' => null]],
                    ]
                ],
                'expected' => 50
            ]
        ];
    }

    public static function dataCheckLoyaltyCoupon()
    {
        return [
            [
                'code' => 'loyalty49844894548',
                'expected' => true
            ],
            [
                'code' => '56556446548484',
                'expected' => false
            ],
            [
                'code' => 'dfhdfh54655pl',
                'expected' => false
            ],
            [
                'code' => '654844pl18498',
                'expected' => false
            ]
        ];
    }

    public static function dataGetEmailsForPersonalCoupon()
    {
        return [
            [
                'email' => 'test1@gmail.com',
                'code' => 'loyalty' . mt_rand()
            ],
            [
                'email' => 'test2@gmail.com',
                'code' => 'loyalty' . mt_rand()
            ],
            [
                'email' => 'test3@gmail.com',
                'expectedCode' => false
            ]
        ];
    }

    public static function dataValidUser()
    {
        $users = self::createUsers();

        return [
            [
                'customer' => $users[0],
                'corporate_enabled' => 'yes',
                'expected' => true,
                'orderCorporate' => false
            ],
            [
                'customer' => $users[1],
                'corporate_enabled' => 'yes',
                'expected' => false,
                'orderCorporate' => false
            ],
            [
                'customer' => $users[1],
                'corporate_enabled' => 'no',
                'expected' => true,
                'orderCorporate' => false
            ],
            [
                'customer' => null,
                'corporate_enabled' => 'yes',
                'expected' => false,
                'orderCorporate' => false

            ],
            [
                'customer' => $users[0],
                'corporate_enabled' => 'yes',
                'expected' => false,
                'orderCorporate' => true
            ]
        ];
    }

    public static function createUsers()
    {
        $customer = new WC_Customer();

        $customer->set_first_name('Tester 1');
        $customer->set_last_name('Tester 1');
        $customer->set_email(uniqid(md5(date('Y-m-d H:i:s'))) . '@mail.com');
        $customer->set_password('password');
        $customer->set_billing_phone('89000000000');
        $customer->set_date_created(date('Y-m-d H:i:s'));
        $customer->save();

        $customer1 = new WC_Customer();

        $customer1->set_first_name('Tester 1');
        $customer1->set_last_name('Tester 1');
        $customer1->set_email(uniqid(md5(date('Y-m-d H:i:s'))) . '@mail.com');
        $customer1->set_password('password');
        $customer1->set_billing_phone('89000000000');
        $customer1->set_date_created(date('Y-m-d H:i:s'));
        $customer1->set_billing_company('OOO TEST');
        $customer1->save();

        return [$customer, $customer1];
    }

    public static function createCoupons($email1 = 'test1@gmail.com', $email2 = 'test2@gmail.com')
    {
        $coupon = new WC_Coupon();

        $coupon->set_usage_limit(0);
        $coupon->set_amount(100);
        $coupon->set_email_restrictions($email1);
        $coupon->set_code('loyalty' . mt_rand());
        $coupon->save();

        $coupon1 = new WC_Coupon();

        $coupon1->set_usage_limit(0);
        $coupon1->set_amount(100);
        $coupon1->set_email_restrictions($email2);
        $coupon1->set_code('loyalty' . mt_rand());
        $coupon1->save();

        return [$coupon, $coupon1];
    }
}
