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

class WC_Retailcrm_Customer_Switcher_State_Test extends WC_Retailcrm_Test_Case_Helper
{
    public function test_feasible()
    {
        $state = new WC_Retailcrm_Customer_Switcher_State();

        $this->assertFalse($state->feasible());

        $state->setNewCustomer(array())
            ->setNewContact(array())
            ->setNewCompanyName('');
        $this->assertFalse($state->feasible());

        $state->setNewCustomer(array('id' => 1));
        $this->assertTrue($state->feasible());

        $state->setNewCustomer(array())
            ->setNewContact(array('id' => 1));
        $this->assertTrue($state->feasible());

        $state->setNewCustomer(array())
            ->setNewContact(array())
            ->setNewCompanyName('test');
        $this->assertTrue($state->feasible());

        $state->setNewCustomer(array())
            ->setNewContact(array())
            ->setNewCompany(array('name' => 'test', 'address' => 'address_test'));
        $this->assertTrue($state->feasible());

        $state->setNewCustomer(array())
            ->setNewContact(array())
            ->setNewCompanyName('');
        $this->assertFalse($state->feasible());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_validate_empty()
    {
        $state = new WC_Retailcrm_Customer_Switcher_State();

        $state->validate();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_validate_order()
    {
        $state = new WC_Retailcrm_Customer_Switcher_State();

        $state->setWcOrder(new WC_Order())->validate();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_validate_customer_and_contact_set()
    {
        $state = new WC_Retailcrm_Customer_Switcher_State();

        $state->setWcOrder(new WC_Order())
            ->setNewCustomer(array('id' => 1))
            ->setNewContact(array('id' => 1))
            ->validate();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function test_validate_ok()
    {
        $state = new WC_Retailcrm_Customer_Switcher_State();

        $state->setWcOrder(new WC_Order())
            ->setNewCustomer(array('id' => 1))
            ->validate();
    }
}

