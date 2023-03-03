<?php

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Abstracts_Settings_Test - Testing WC_Retailcrm_Abstracts_Settings.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_Abstracts_Settings_Test extends  WC_Retailcrm_Test_Case_Helper
{
    /**
     * @var WC_Retailcrm_Base
     */
    protected $base;

    public function setUp()
    {
        $this->base = new WC_Retailcrm_Base();

        parent::setUp();
    }

    /**
     * @param $checkout
     *
     * @dataProvider dataProviderAssistant
     */
    public function test_validate_online_assistant_field($checkout)
    {
        $_POST['woocommerce_integration-retailcrm_online_assistant'] = $checkout;

        $onlineAssistant = $this->base->validate_online_assistant_field('', '');

        $this->assertInternalType('string', $onlineAssistant);

        if (is_string($checkout)) {
            $this->assertEquals('jscode', $onlineAssistant);
        } else {
            $this->assertEquals('', $onlineAssistant);
        }
    }

    public function test_validate_payments()
    {
        $this->register_lagacy_proxy_static_mocks;
        $paymentGateway = new WC_Payment_Gateways();

        $enabledPayments = $paymentGateway->get_available_payment_gateways();
        $this->assertCount(0, $enabledPayments);

        $payments = $paymentGateway->payment_gateways();
        $keys = array_keys($payments);
        $payments[$keys[0]]->method_title = null;
        $payments[$keys[2]]->method_description = null;
        $preparePayments = [];

        foreach ($payments as $payment) {
            $title = '';
            $description = '';

            if (empty($payment->method_title)) {
                $title = $payment->id;
            } else {
                $title = $payment->method_title;
            }

            if (empty($payment->method_description)) {
                $description = $payment->description;
            } else {
                $description = $payment->method_description;
            }

            $preparePayments[$payment->id] = [
                'css'         => 'min-width:350px;',
                'type'        => 'select',
                'title'       => $title,
                'class'       => 'select',
                'desc_tip'    =>  true,
                'description' => $description,
            ];
        }

        $this->assertEquals($payments[$keys[0]]->id, $preparePayments[$keys[0]]['title']);
        $this->assertEquals($payments[$keys[2]]->description, $preparePayments[$keys[2]]['description']);


    }

    public function dataProviderAssistant()
    {
        return array(
            array(
                'checkout' => 'js\code'
            ),
            array(
                'checkout' => null
            ),
            array(
                'checkout' => array()
            )
        );
    }
}
