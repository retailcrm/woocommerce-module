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
            $this->assertEquals('c.retailcrm.tech/widget/loader.js', $onlineAssistant);
        } else {
            $this->assertEquals('', $onlineAssistant);
        }
    }


    public function dataProviderAssistant()
    {
        return array(
            array(
                'checkout' => 'c.retailcrm.tech/widget/loader.js'
            ),
            array(
                'checkout' => null
            )
        );
    }
}
