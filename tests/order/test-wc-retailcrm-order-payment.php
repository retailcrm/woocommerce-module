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

class WC_Retailcrm_Order_Payment_Test extends WC_Retailcrm_Test_Case_Helper
{
    /** @var WC_Order */
    protected $order;

    public function setUp()
    {
        parent::setUp();

        $this->order = WC_Helper_Order::create_order();
        $this->setOptions();
    }

    /**
     * @param mixed $externalId
     *
     * @dataProvider dataProvider
     */
    public function test_build($externalId)
    {
	    $settings = $this->getOptions();
	    $order_payment = new WC_Retailcrm_Order_Payment($settings);

        $data = $order_payment->build($this->order, $externalId)->get_data();

        $this->assertNotEmpty($data);

        if (!empty($externalId)) {
	        $this->assertArrayHasKey('externalId', $data);
        }

        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('order', $data);
    }

    /**
     * @param mixed $externalId
     *
     * @dataProvider dataProvider
     */
    public function test_build_payment_type_not_exist($externalId)
    {
        $order_payment = new WC_Retailcrm_Order_Payment('test');
        $data = $order_payment->build($this->order, $externalId)->get_data();

        $this->assertEmpty($data);
    }


    /**
     * @param mixed $externalId
     *
     * @dataProvider dataProvider
     */
    public function test_not_new_payment($externalId)
    {
        $settings = $this->getOptions();
        $order_payment = new WC_Retailcrm_Order_Payment($settings);
        $order_payment->is_new = false;

        $data = $order_payment->build($this->order, $externalId)->get_data();

        $this->assertEmpty($data);
    }


    /**
     * @param mixed $externalId
     *
     * @dataProvider dataProvider
     */
    public function test_order_paid($externalId)
    {
        $settings = $this->getOptions();
        $order_payment = new WC_Retailcrm_Order_Payment($settings);

        $this->order->update_status('completed');

        $data = $order_payment->build($this->order, $externalId)->get_data();

        $this->assertNotEmpty($data);

        if (!empty($externalId)) {
            $this->assertArrayHasKey('externalId', $data);
        }

        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('order', $data);
    }


    /**
     * @param mixed $externalId
     *
     * @dataProvider dataProvider
     */
    public function test_build_with_amount($externalId)
    {
        $settings = $this->getOptions();
        $order_payment = new WC_Retailcrm_Order_Payment($settings);

        $data = $order_payment->build($this->order, $externalId)->get_data();

	    $this->assertNotEmpty($data);

	    if (!empty($externalId)) {
		    $this->assertArrayHasKey('externalId', $data);
	    }

	    $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('order', $data);
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return array(
            array(
                'externalId' => false
            ),
            array(
                'externalId' => uniqid()
            )
        );
    }
}
