<?php

class WC_Retailcrm_Google_Analytics_Test extends WC_Retailcrm_Test_Case_Helper
{
    private $ga;
    private $options;
    private $order;
    private $orderKey;

    public function setUp()
    {
        $this->order = WC_Helper_Order::create_order(0);
        $this->orderKey = $this->order->get_order_key();
        $this->setOptions();

        $this->options = get_option(WC_Retailcrm_Base::$option_key);
        $this->ga = WC_Retailcrm_Google_Analytics::getInstance($this->options);
    }

    public function test_initialize_analytics()
    {
        $js = $this->ga->initialize_analytics();

        $this->assertContains($this->options['ua_code'], $js);
        $this->assertContains($this->options['ua_custom'], $js);
    }

    /**
     * @param $checkout
     *
     * @dataProvider dataProvider
     */
    public function test_send_analytics($checkout)
    {
        if ($checkout === true) {
            $_GET['key'] = $this->orderKey;
        } elseif (is_null($checkout)) {
            $_GET['key'] = '';
        }

        $js = $this->ga->send_analytics();

        if ($checkout) {
            $this->assertContains((string)$this->order->get_id(), $js);
            $this->assertContains((string)$this->order->get_total(), $js);
            $this->assertContains((string)$this->order->get_total_tax(), $js);
            $this->assertContains((string)$this->order->get_shipping_total(), $js);
        } else {
            $this->assertEmpty($js);
        }
    }

    public function dataProvider()
    {
        return array(
            array(
                'checkout' => false
            ),
            array(
                'checkout' => null
            ),
            array(
                'checkout' => true
            )
        );
    }
}
