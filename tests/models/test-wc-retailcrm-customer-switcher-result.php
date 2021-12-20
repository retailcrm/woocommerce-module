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

class WC_Retailcrm_Customer_Switcher_Result_Test extends WC_Retailcrm_Test_Case_Helper
{
    protected $customer;

    public function  setUp()
    {
        $this->customer = new WC_Customer();

        $this->customer->set_first_name('Tester');
        $this->customer->set_last_name('Tester');
        $this->customer->set_email( uniqid(md5(date('Y-m-d H:i:s'))) . '@mail.com');
        $this->customer->set_password('password');
        $this->customer->set_billing_phone('89000000000');
        $this->customer->set_date_created(date('Y-m-d H:i:s'));
        $this->customer->save();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_invalid_both()
    {
        new WC_Retailcrm_Customer_Switcher_Result(new stdClass(), new stdClass());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_invalid_customer()
    {
        new WC_Retailcrm_Customer_Switcher_Result(new stdClass(), new WC_Order());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_invalid_order()
    {
        new WC_Retailcrm_Customer_Switcher_Result(new WC_Customer(), new stdClass());
    }

    public function test_valid()
    {
        $result = new WC_Retailcrm_Customer_Switcher_Result($this->customer, new WC_Order());

        $this->assertInstanceOf('\WC_Customer', $result->getWcCustomer());
        $this->assertInstanceOf('\WC_Order', $result->getWcOrder());
    }

    public function test_valid_no_customer()
    {
        $result = new WC_Retailcrm_Customer_Switcher_Result(null, new WC_Order());

        $this->assertEmpty($result->getWcCustomer());
        $this->assertInstanceOf('\WC_Order', $result->getWcOrder());
    }


    public function test_save()
    {
        $switcher = new WC_Retailcrm_Customer_Switcher_Result($this->customer, new WC_Order());

        $switcher->save();
        $this->assertInstanceOf('\WC_Customer', $switcher->getWcCustomer());
    }
}

