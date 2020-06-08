<?php
/**
 * PHP version 5.3
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */

class WC_Retailcrm_WC_Customer_Builder_Test extends WC_Retailcrm_Test_Case_Helper
{
    public function test_empty()
    {
        $this->expectException('\RuntimeException');
        $builder = new WC_Retailcrm_WC_Customer_Builder();
        $builder->build();
    }

    public function test_empty_array()
    {
        $this->expectException('\RuntimeException');
        $builder = new WC_Retailcrm_WC_Customer_Builder();
        $builder->setData(array())->build();
    }

    public function test_not_array()
    {
        $this->expectException('\RuntimeException');
        $builder = new WC_Retailcrm_WC_Customer_Builder();
        $builder->setData(new stdClass())->build();
    }

    /**
     * @dataProvider customerData
     *
     * @param array $customerData
     */
    public function test_build($customerData)
    {
        $builder = new WC_Retailcrm_WC_Customer_Builder();
        $wcCustomer = $builder->setData($customerData)->build()->getResult();

        $this->assertInstanceOf('\WC_Customer', $wcCustomer);

        if (isset($customerData['firstName'])) {
            $this->assertEquals($customerData['firstName'], $wcCustomer->get_first_name());
        }

        if (isset($customerData['lastName'])) {
            $this->assertEquals($customerData['lastName'], $wcCustomer->get_last_name());
        }

        if (isset($customerData['email'])) {
            $this->assertEquals($customerData['email'], $wcCustomer->get_billing_email());
        }

        if (isset($customerData['phones']) && count($customerData['phones']) > 0) {
            $this->assertEquals($customerData['phones'][0]['number'], $wcCustomer->get_billing_phone());
        }

        if (isset($customerData['address']) && !empty($customerData['address'])) {
            $address = $customerData['address'];

            if (isset($address['region'])) {
                $this->assertEquals($address['region'], $wcCustomer->get_billing_state());
            }

            if (isset($address['index'])) {
                $this->assertEquals($address['index'], $wcCustomer->get_billing_postcode());
            }

            if (isset($address['country'])) {
                $this->assertEquals($address['country'], $wcCustomer->get_billing_country());
            }

            if (isset($address['city'])) {
                $this->assertEquals($address['city'], $wcCustomer->get_billing_city());
            }
        }
    }

    public function customerData()
    {
        return array(
            array(
                'customer' => array(
                    'type' => 'customer',
                    'id' => 4228,
                    'externalId' => '2',
                )
            ),
            array(
                'customer' => array(
                    'type' => 'customer',
                    'id' => 4228,
                    'externalId' => '2',
                    'isContact' => false,
                    'createdAt' => '2020-06-01 15:31:46',
                    'managerId' => 27,
                    'vip' => false,
                    'bad' => false,
                    'site' => 'bitrix-test',
                    'contragent' => array(
                        'contragentType' => 'individual',
                    ),
                    'tags' => array(),
                    'marginSumm' => 9412,
                    'totalSumm' => 9412,
                    'averageSumm' => 9412,
                    'ordersCount' => 1,
                    'costSumm' => 0,
                    'customFields' => array(),
                    'personalDiscount' => 0,
                    'cumulativeDiscount' => 0,
                    'address' => array(
                        'id' => 3132,
                        'text' => 'ул. Пушкина дом Колотушкина',
                    ),
                    'segments' => array(),
                    'firstName' => 'tester001',
                    'lastName' => 'tester001',
                    'email' => 'tester001@example.com',
                    'emailMarketingUnsubscribedAt' => '2020-06-01 15:34:23',
                    'phones' => array(array('number' => '2354708915097'))
                )
            )
        );
    }
}
