<?php

class WC_Retailcrm_Customers_Test extends WC_Unit_Test_Case
{
    protected $apiMock;
    protected $responseMock;
    protected $customer;

    public function setUp()
    {
        $this->responseMock = $this->getMockBuilder('\WC_Retailcrm_Response')
            ->disableOriginalConstructor()
            ->setMethods(array(
                'isSuccessful'
            ))
            ->getMock();

        $this->apiMock = $this->getMockBuilder('\WC_Retailcrm_Proxy')
            ->disableOriginalConstructor()
            ->setMethods(array(
                'ordersGet',
                'ordersUpload',
                'ordersCreate',
                'ordersEdit',
                'customersGet',
                'customersUpload',
                'customersCreate',
                'customersEdit'
            ))
            ->getMock();

        $this->customer = new WC_Customer();
        $this->customer->set_email(uniqid(md5(date('Y-m-d H:i:s'))) . '@mail.com');
        $this->customer->set_password('password');
        $this->customer->set_role(WC_Retailcrm_Customers::CUSTOMER_ROLE);
        $this->customer->set_billing_phone('89000000000');
        $this->customer->save();
    }

    /**
     * @param retailcrm
     * @dataProvider dataProviderApiClient
     */
    public function test_wc_customer_get($retailcrm)
    {
        $wc_customer = new WC_Customer($this->customer->get_id());
        $retailcrm_customer = new WC_Retailcrm_Customers($retailcrm);
        $this->assertEquals($wc_customer, $retailcrm_customer->wcCustomerGet($this->customer->get_id()));
    }

    /**
     * @param retailcrm
     * @dataProvider dataProviderApiClient
     */
    public function test_customers_upload($retailcrm)
    {
        $retailcrm_customer = new WC_Retailcrm_Customers($retailcrm);
        $data = $retailcrm_customer->customersUpload();

        if ($retailcrm) {
            $this->assertInternalType('array', $data);
            $this->assertInternalType('array', $data[0]);
            $this->assertArrayHasKey('externalId', $data[0][0]);
        } else {
            $this->assertEquals(null, $data);
        }
    }

    /**
     * @param $retailcrm
     * @dataProvider dataProviderApiClient
     */
    public function test_create_customer($retailcrm)
    {
        $retailcrm_customer = new WC_Retailcrm_Customers($retailcrm);
        $customer = $retailcrm_customer->createCustomer($this->customer->get_id());
        $customer_send = $retailcrm_customer->getCustomer();

        if ($retailcrm) {
            $this->assertArrayHasKey('externalId', $customer_send);
            $this->assertArrayHasKey('firstName', $customer_send);
            $this->assertArrayHasKey('createdAt', $customer_send);
            $this->assertArrayHasKey('email', $customer_send);
            $this->assertNotEmpty($customer_send['externalId']);
            $this->assertNotEmpty($customer_send['firstName']);
            $this->assertNotEmpty($customer_send['email']);
            $this->assertInstanceOf('WC_Customer', $customer);
        } else {
            $this->assertEquals(null, $customer);
            $this->assertEquals(array(), $customer_send);
        }
    }

    /**
     * @param $retailcrm
     * @dataProvider dataProviderApiClient
     */
    public function test_update_customer($retailcrm)
    {
        $retailcrm_customer = new WC_Retailcrm_Customers($retailcrm);
        $customer = $retailcrm_customer->updateCustomer($this->customer->get_id());
        $customer_send = $retailcrm_customer->getCustomer();

        if ($retailcrm) {
            $this->assertArrayHasKey('externalId', $customer_send);
            $this->assertArrayHasKey('firstName', $customer_send);
            $this->assertArrayHasKey('createdAt', $customer_send);
            $this->assertArrayHasKey('email', $customer_send);
            $this->assertNotEmpty($customer_send['externalId']);
            $this->assertNotEmpty($customer_send['firstName']);
            $this->assertNotEmpty($customer_send['email']);
            $this->assertInstanceOf('WC_Customer', $customer);
        } else {
            $this->assertEquals(null, $customer);
            $this->assertEquals(array(), $customer_send);
        }
    }

    public function dataProviderApiClient()
    {
        $this->setUp();

        return array(
            array(
                'retailcrm' => $this->apiMock
            ),
            array(
                'retailcrm' => false
            )
        );
    }
}