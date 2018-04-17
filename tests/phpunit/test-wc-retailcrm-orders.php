<?php

class WC_Retailcrm_Orders_Test extends  WC_Unit_Test_Case
{
    protected $apiMock;
    protected $responseMock;
    protected $order;

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
                'customersCreate'
            ))
            ->getMock();

	    $this->apiMock->expects($this->any())
		    ->method('ordersEdit')
		    ->willReturn($this->responseMock);

        $this->order = new WC_Order();
        $this->order->save();
        parent::setUp();
    }

    /**
     * @param $retailcrm
     * @dataProvider dataProviderRetailcrm
     */
    public function test_order_upload($retailcrm)
    {
        $retailcrm_orders = new WC_Retailcrm_Orders($retailcrm);
        $retailcrm_orders->ordersUpload();
    }

    /**
     * @param $retailcrm
     * @dataProvider dataProviderRetailcrm
     */
    public function test_order_create($retailcrm)
    {
        $retailcrm_orders = new WC_Retailcrm_Orders($retailcrm);
        $retailcrm_orders->orderCreate($this->order->get_id());
    }

    /**
     * @param $retailcrm
     * @dataProvider dataProviderRetailcrm
     */
    public function test_order_update_status($retailcrm)
    {
        $retailcrm_orders = new WC_Retailcrm_Orders($retailcrm);
        $retailcrm_orders->orderUpdateStatus($this->order->get_id());
    }

    /**
     * @param $retailcrm
     * @dataProvider dataProviderRetailcrm
     */
    public function test_order_update_payment($retailcrm)
    {
        $retailcrm_orders = new WC_Retailcrm_Orders($retailcrm);
        $retailcrm_orders->orderUpdatePayment($this->order->get_id());
    }

    /**
     * @param $isSuccessful
     * @param $retailcrm
     * @dataProvider dataProviderUpdateOrder
     */
    public function test_update_order($isSuccessful, $retailcrm)
    {
        $this->responseMock->expects($this->any())
            ->method('isSuccessful')
            ->willReturn($isSuccessful);

        $retailcrm_orders = new WC_Retailcrm_Orders($retailcrm);
        $retailcrm_orders->updateOrder($this->order->get_id());
    }

    public function dataProviderUpdateOrder()
    {
	    $this->setUp();

        return array(
            array(
                'is_successful' => true,
                'retailcrm' => $this->apiMock
            ),
            array(
                'is_successful' => false,
                'retailcrm' => $this->apiMock
            ),
            array(
                'is_successful' => true,
                'retailcrm' => false
            ),
            array(
                'is_successful' => false,
                'retailcrm' => false
            )
        );
    }

    public function dataProviderRetailcrm()
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
