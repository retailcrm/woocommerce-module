<?php
/**
 * PHP version 5.6
 *
 * Class WC_Retailcrm_Daemon_Collector_Test - Testing WC_Retailcrm_Daemon_Collector.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_Daemon_Collector_Test extends WC_Retailcrm_Test_Case_Helper
{
    private $daemonCollector;
    private $options;

    public function setUp()
    {
        $this->options = array(
            'daemon_collector_key' => 'RC-XXXXXXXXXX-X'
        );

        $this->daemonCollector = WC_Retailcrm_Daemon_Collector::getInstance($this->options);
    }

    public function test_initialize_daemon_collector()
    {
        $customerObject = WC_Helper_Customer::create_customer();
        WC()->customer = $customerObject;

        $js = $this->daemonCollector->initialize_daemon_collector();

        $this->assertContains('customerId', $js);
        $this->assertContains($this->options['daemon_collector_key'], $js);
        $this->assertContains('<script', $js);
        $this->assertContains('</script>', $js);
        $this->assertContains('_rc(\'create\',', $js);
    }
}
