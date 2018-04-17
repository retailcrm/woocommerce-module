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
        $this->customer->set_role('customer');
        $this->customer->save();
    }

    /**
     * @param retailcrm
     * @dataProvider dataProviderApiClient
     */
    public function test_customers_upload($retailcrm)
    {
        $retailcrm_customer = new WC_Retailcrm_Customers($retailcrm);
        $retailcrm_customer->customersUpload();
    }

	/**
	 * @param $retailcrm
	 * @dataProvider dataProviderApiClient
	 */
    public function test_create_customer($retailcrm)
    {
        $retailcrm_customer = new WC_Retailcrm_Customers($retailcrm);
        $retailcrm_customer->createCustomer($this->customer->get_id());
    }

	/**
	 * @param $retailcrm
	 * @dataProvider dataProviderApiClient
	 */
    public function test_update_customer($retailcrm)
    {
        $retailcrm_customer = new WC_Retailcrm_Customers($retailcrm);
        $retailcrm_customer->updateCustomer($this->customer->get_id());
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