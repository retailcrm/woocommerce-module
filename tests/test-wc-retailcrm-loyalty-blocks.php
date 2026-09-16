<?php

/**
 * PHP version 7.1
 *
 * Class WC_Retailcrm_Loyalty_Blocks_Test
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_Loyalty_Blocks_Test extends WC_Retailcrm_Test_Case_Helper
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|WC_Retailcrm_Loyalty */
    protected $loyaltyMock;

    public function setUp()
    {
        parent::setUp();

        $this->loyaltyMock = $this->getMockBuilder(WC_Retailcrm_Loyalty::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'hasActiveLoyaltyAccount',
                'getAppliedLoyaltyBonuses',
                'getMaxChargeBonuses',
                'calculateCreditBonuses',
                'hasCartCalculationError',
            ])
            ->getMock();

        if (!WC()->cart) {
            wc_load_cart();
        }

        WC()->cart->empty_cart();

        if (WC()->session) {
            WC()->session->set(WC_Retailcrm_Loyalty_Blocks::CACHE_SESSION_KEY, null);
        }
    }

    public function testGetDataForGuest()
    {
        $this->fillCart();
        $this->loyaltyMock->expects($this->never())->method('hasActiveLoyaltyAccount');

        $this->assertEquals($this->getDefaultData(), $this->getBlocks(0)->get_data());
    }

    public function testGetDataForEmptyCart()
    {
        $this->loyaltyMock->expects($this->never())->method('hasActiveLoyaltyAccount');

        $this->assertEquals($this->getDefaultData(), $this->getBlocks(1)->get_data());
    }

    public function testGetDataWithoutActiveAccount()
    {
        $this->fillCart();
        $this->loyaltyMock->method('hasActiveLoyaltyAccount')->willReturn(false);
        $this->loyaltyMock->expects($this->never())->method('getMaxChargeBonuses');

        $this->assertEquals($this->getDefaultData(), $this->getBlocks(1)->get_data());
    }

    public function testGetData()
    {
        $this->fillCart();
        $this->loyaltyMock->method('hasActiveLoyaltyAccount')->willReturn(true);
        $this->loyaltyMock->method('getAppliedLoyaltyBonuses')->willReturn(0.0);
        $this->loyaltyMock->method('getMaxChargeBonuses')->willReturn(['maxCharge' => 50.0, 'chargeRate' => 2.0]);
        $this->loyaltyMock->method('calculateCreditBonuses')->willReturn(15.0);

        $this->assertEquals(
            [
                'active' => true,
                'maxCharge' => 50.0,
                'chargeRate' => 2.0,
                'appliedBonus' => 0.0,
                'creditBonuses' => 15.0,
            ],
            $this->getBlocks(1)->get_data()
        );
    }

    public function testGetDataWithAppliedBonuses()
    {
        $this->fillCart();
        $this->loyaltyMock->method('hasActiveLoyaltyAccount')->willReturn(true);
        $this->loyaltyMock->method('getAppliedLoyaltyBonuses')->willReturn(30.0);
        $this->loyaltyMock->expects($this->never())->method('getMaxChargeBonuses');
        $this->loyaltyMock->method('calculateCreditBonuses')->willReturn(10.0);

        $data = $this->getBlocks(1)->get_data();

        $this->assertTrue($data['active']);
        $this->assertEquals(0.0, $data['maxCharge']);
        $this->assertEquals(30.0, $data['appliedBonus']);
        $this->assertEquals(10.0, $data['creditBonuses']);
    }

    public function testGetDataHandlesException()
    {
        $this->fillCart();
        $this->loyaltyMock->method('hasActiveLoyaltyAccount')->willThrowException(new Exception('API error'));

        $this->assertEquals($this->getDefaultData(), $this->getBlocks(1)->get_data());
    }

    public function testGetDataIsCachedInSession()
    {
        if (!WC()->session) {
            $this->markTestSkipped('WooCommerce session is not available');
        }

        $this->fillCart();
        $this->loyaltyMock->expects($this->once())->method('hasActiveLoyaltyAccount')->willReturn(true);
        $this->loyaltyMock->method('getAppliedLoyaltyBonuses')->willReturn(0.0);
        $this->loyaltyMock->method('getMaxChargeBonuses')->willReturn(null);
        $this->loyaltyMock->method('calculateCreditBonuses')->willReturn(15.0);

        $blocks = $this->getBlocks(1);

        $this->assertEquals($blocks->get_data(), $blocks->get_data());
    }

    public function testGetDataIsNotCachedOnCalculationError()
    {
        if (!WC()->session) {
            $this->markTestSkipped('WooCommerce session is not available');
        }

        $this->fillCart();
        $this->loyaltyMock->expects($this->exactly(2))->method('hasActiveLoyaltyAccount')->willReturn(true);
        $this->loyaltyMock->method('getAppliedLoyaltyBonuses')->willReturn(0.0);
        $this->loyaltyMock->method('getMaxChargeBonuses')->willReturn(null);
        $this->loyaltyMock->method('calculateCreditBonuses')->willReturn(0.0);
        $this->loyaltyMock->method('hasCartCalculationError')->willReturn(true);

        $blocks = $this->getBlocks(1);
        $blocks->get_data();
        $blocks->get_data();
    }

    public function testSchemaDescribesAllDataFields()
    {
        $blocks = $this->getBlocks(0);

        $this->assertEquals(array_keys($this->getDefaultData()), array_keys($blocks->get_schema()));
    }

    public function testEnqueueAssets()
    {
        $blocks = $this->getBlocks(0);
        $blocks->enqueue_assets();
        $blocks->enqueue_assets();

        $this->assertTrue(wp_script_is(WC_Retailcrm_Loyalty_Blocks::SCRIPT_HANDLE));
        $this->assertContains(
            'RetailcrmLoyaltyBlocks',
            (string) wp_scripts()->get_data(WC_Retailcrm_Loyalty_Blocks::SCRIPT_HANDLE, 'data')
        );

        wp_dequeue_script(WC_Retailcrm_Loyalty_Blocks::SCRIPT_HANDLE);
        wp_deregister_script(WC_Retailcrm_Loyalty_Blocks::SCRIPT_HANDLE);
    }

    private function getBlocks(int $customerId)
    {
        $blocks = $this->getMockBuilder(WC_Retailcrm_Loyalty_Blocks::class)
            ->setConstructorArgs([$this->loyaltyMock])
            ->setMethods(['getCustomerId'])
            ->getMock();

        $blocks->method('getCustomerId')->willReturn($customerId);

        return $blocks;
    }

    private function fillCart()
    {
        WC()->cart->add_to_cart(WC_Helper_Product::create_simple_product()->get_id(), 1);
    }

    private function getDefaultData()
    {
        return [
            'active' => false,
            'maxCharge' => 0.0,
            'chargeRate' => 1.0,
            'appliedBonus' => 0.0,
            'creditBonuses' => 0.0,
        ];
    }
}
