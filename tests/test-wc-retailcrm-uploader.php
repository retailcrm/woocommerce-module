<?php

class WC_Retailcrm_Uploader_Test extends WC_Retailcrm_Test_Case_Helper
{
    protected $apiMock;
    protected $responseMock;
    protected $customer;
    private $order;

    public function setUp()
    {
        $this->responseMock = $this->getMockBuilder('\WC_Retailcrm_Response')
                                   ->disableOriginalConstructor()
                                   ->setMethods(array('isSuccessful'))
                                   ->getMock();

        $this->apiMock = $this->getMockBuilder('\WC_Retailcrm_Proxy')
                              ->disableOriginalConstructor()
                              ->setMethods(array(
                                  'customersUpload',
                                  'customersCreate',
                                  'uploadArchiveCustomers',
                                  'uploadArchiveOrders',
                                  'getCountUsers',
                                  'getCountOrders',
                                  'customersGet',
                                  'customersList',
                                  'ordersCreate'
                              ))
                              ->getMock();


        $this->setMockResponse($this->responseMock, 'isSuccessful', true);
        $this->setMockResponse(
            $this->apiMock,
            'customersList',
            array('success' => true, 'customers' => array(array('externalId' => 1)))
        );
        $this->setMockResponse($this->apiMock, 'customersCreate', $this->responseMock);

        $this->customer = new WC_Customer();
        $this->customer->set_first_name('Tester');
        $this->customer->set_last_name('Tester');
        $this->customer->set_email(uniqid(md5(date('Y-m-d H:i:s'))) . '@mail.com');
        $this->customer->set_billing_email($this->customer->get_email());
        $this->customer->set_password('password');
        $this->customer->set_billing_phone('89000000000');
        $this->customer->set_date_created(date('Y-m-d H:i:s'));
        $this->customer->save();

        $this->order = WC_Helper_Order::create_order();
    }

    /**
     * @param retailcrm
     * @dataProvider dataProviderApiClient
     */
    public function test_customers_upload($retailcrm)
    {
        $retailcrm_uploader = $this->getRetailcrmUploader($retailcrm);
        $data = $retailcrm_uploader->uploadArchiveCustomers(0);

        if ($retailcrm) {
            $this->assertInternalType('array', $data);
            $this->assertInternalType('array', $data[0]);
            $this->assertArrayHasKey('externalId', $data[0]);
        } else {
            $this->assertEquals(null, $data);
        }
    }

    /**
     * @param $retailcrm
     * @dataProvider dataProviderApiClient
     */
    public function test_order_upload($retailcrm)
    {
        $retailcrm_uploader = $this->getRetailcrmUploader($retailcrm);
        $data = $retailcrm_uploader->uploadArchiveOrders(0);

        if ($retailcrm) {
            $this->assertInternalType('array', $data);
        } else {
            $this->assertEquals(null, $data);
        }
    }

    public function test_get_count_orders_upload()
    {
        $retailcrm_uploader = $this->getRetailcrmUploader($this->apiMock);
        $count_orders = $retailcrm_uploader->getCountOrders();

        $this->assertInternalType('int', $count_orders);
    }

    public function test_get_count_users_upload()
    {
        $retailcrm_uploader = $this->getRetailcrmUploader($this->apiMock);
        $count_users = $retailcrm_uploader->getCountUsers();

        $this->assertInternalType('int', $count_users);
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

    /**
     * @param $retailcrm
     *
     * @return WC_Retailcrm_Customers
     */
    private function getRetailcrmUploader($retailcrm)
    {
        $customer = new WC_Retailcrm_Customers(
            $retailcrm,
            $this->getOptions(),
            new WC_Retailcrm_Customer_Address()
        );

        $order = new WC_Retailcrm_Orders(
            $retailcrm,
            $this->getOptions(),
            new WC_Retailcrm_Order_Item($this->getOptions()),
            new WC_Retailcrm_Order_Address(),
            new WC_Retailcrm_Customers($retailcrm, $this->getOptions(), new WC_Retailcrm_Customer_Address()),
            new WC_Retailcrm_Order($this->getOptions()),
            new WC_Retailcrm_Order_Payment($this->getOptions())
        );

        return new WC_Retailcrm_Uploader($retailcrm, $order, $customer);
    }
}
