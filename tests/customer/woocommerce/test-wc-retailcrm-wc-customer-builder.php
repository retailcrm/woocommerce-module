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
    protected $customer;

    public function setUp()
    {
        $this->customer = new WC_Customer();

        $this->customer->set_first_name('Tester First Name');
        $this->customer->set_last_name('Tester Last Name');
        $this->customer->set_email(uniqid(md5(date('Y-m-d H:i:s'))) . '@mail.com');
        $this->customer->set_password('password');
        $this->customer->set_billing_phone('89000000000');
        $this->customer->set_date_created(date('Y-m-d H:i:s'));
        $this->customer->save();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function test_empty()
    {
        $builder = new WC_Retailcrm_WC_Customer_Builder();

        $this->assertEmpty($builder->build()->getResult());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function test_empty_array()
    {
        $builder = new WC_Retailcrm_WC_Customer_Builder();

        $this->assertEmpty($builder->setData(array())->build()->getResult());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function test_not_array()
    {
        $builder = new WC_Retailcrm_WC_Customer_Builder();

        $this->assertEquals('test', $builder->setData('test')->build()->getResult());
    }

    /**
     * @dataProvider customerData
     *
     * @param $customerData
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

    public function test_set_field()
    {
        $builder = new WC_Retailcrm_WC_Customer_Builder();
        $customerData = $this->customerData()[1]['customer'];
        $wcCustomer = $builder
            ->setFirstName($customerData['firstName'])
            ->setLastName($customerData['lastName'])
            ->setEmail($customerData['email'])
            ->setExternalId($customerData['externalId'])
            ->setPhones($customerData['phones'])
            ->setAddress($customerData['address'])
            ->build()
            ->getResult();

        $this->assertInstanceOf('\WC_Customer', $wcCustomer);
        $this->assertEquals($customerData['firstName'], $wcCustomer->get_first_name());
        $this->assertEquals($customerData['lastName'], $wcCustomer->get_last_name());
        $this->assertEquals($customerData['email'], $wcCustomer->get_billing_email());
        $this->assertEquals($customerData['phones'][0]['number'], $wcCustomer->get_billing_phone());

        $address = $customerData['address'];

        $this->assertEquals($address['region'], $wcCustomer->get_billing_state());
        $this->assertEquals($address['index'], $wcCustomer->get_billing_postcode());
        $this->assertEquals($address['country'], $wcCustomer->get_billing_country());
        $this->assertEquals($address['city'], $wcCustomer->get_billing_city());
    }

    public function test_phone_string()
    {
        $builder = new WC_Retailcrm_WC_Customer_Builder();

        $customerData = $this->customerData()[1]['customer'];
        $customerData['phones'] = '123454567';

        $wcCustomer = $builder->setData($customerData)->build()->getResult();

        $this->assertInstanceOf('\WC_Customer', $wcCustomer);
        $this->assertEquals('123454567', $wcCustomer->get_billing_phone());
    }

    public function test_set_phone_string()
    {
        $builder = new WC_Retailcrm_WC_Customer_Builder();
        $customerData = $this->customerData()[1]['customer'];
        $wcCustomer = $builder->setData($customerData)->setPhones('123456789')->build()->getResult();

        $this->assertInstanceOf('\WC_Customer', $wcCustomer);
        $this->assertEquals($customerData['phones'][0]['number'], $wcCustomer->get_billing_phone());
    }

    public function test_set_address_empty()
    {
        $builder = new WC_Retailcrm_WC_Customer_Builder();
        $customerData = $this->customerData()[1]['customer'];

        $wcCustomer = $builder->setData($customerData)->setAddress(array())->build()->getResult();

        $this->assertInstanceOf('\WC_Customer', $wcCustomer);

        $this->assertEmpty($wcCustomer->get_billing_state());
        $this->assertEmpty($wcCustomer->get_billing_postcode());
        $this->assertEmpty($wcCustomer->get_billing_country());
        $this->assertEmpty($wcCustomer->get_billing_city());
    }

    public function test_set_wc_customer()
    {
        $builder = new WC_Retailcrm_WC_Customer_Builder();
        $wcCustomer = $builder->setWcCustomer($this->customer)->getResult();

        $this->assertInstanceOf('\WC_Customer', $wcCustomer);
        $this->assertEquals($this->customer->get_id(), $wcCustomer->get_id());
        $this->assertEquals($this->customer->get_first_name(), $wcCustomer->get_first_name());
        $this->assertEquals($this->customer->get_last_name(), $wcCustomer->get_last_name());
        $this->assertEquals($this->customer->get_billing_phone(), $wcCustomer->get_billing_phone());
        $this->assertEquals($this->customer->get_email(), $wcCustomer->get_email());
    }

    public function test_set_not_wc_customer()
    {
        $builder = new WC_Retailcrm_WC_Customer_Builder();
        $wcCustomer = $builder->setWcCustomer(null)->getResult();

        $this->assertInstanceOf('\WC_Customer', $wcCustomer);
        $this->assertEquals(null, $wcCustomer->get_id());
        $this->assertEquals(null, $wcCustomer->get_first_name());
        $this->assertEquals(null, $wcCustomer->get_last_name());
        $this->assertEquals(null, $wcCustomer->get_billing_phone());
        $this->assertEquals(null, $wcCustomer->get_email());
    }

    public function test_load_wc_customer_by_id()
    {
        $builder = new WC_Retailcrm_WC_Customer_Builder();
        $isValidExternalId = $builder->loadExternalId($this->customer->get_id());
        $wcCustomer = $builder->getResult();

        $this->assertInstanceOf('\WC_Customer', $wcCustomer);
        $this->assertEquals(true, $isValidExternalId);
        $this->assertEquals($this->customer->get_id(), $wcCustomer->get_id());
        $this->assertEquals($this->customer->get_first_name(), $wcCustomer->get_first_name());
        $this->assertEquals($this->customer->get_last_name(), $wcCustomer->get_last_name());
        $this->assertEquals($this->customer->get_billing_phone(), $wcCustomer->get_billing_phone());
        $this->assertEquals($this->customer->get_email(), $wcCustomer->get_email());
    }

    public function test_load_wc_customer_by_not_valid_id()
    {
        $builder = new WC_Retailcrm_WC_Customer_Builder();

        $builder->loadExternalId(null);

        $wcCustomer = $builder->getResult();

        $this->assertInstanceOf('\WC_Customer', $wcCustomer);

        $this->assertEquals(null, $wcCustomer->get_id());
        $this->assertEquals(null, $wcCustomer->get_first_name());
        $this->assertEquals(null, $wcCustomer->get_last_name());
        $this->assertEquals(null, $wcCustomer->get_billing_phone());
        $this->assertEquals(null, $wcCustomer->get_email());
    }

    /**
     * @return array
     */
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
                        'id'      => 3132,
                        'text'    => 'street_test',
                        'region'  => 'region_test',
                        'index'   => '112233',
                        'country' => 'country_test',
                        'city'    => 'city_test'
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

