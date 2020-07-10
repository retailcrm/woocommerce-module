<?php

class WC_Retailcrm_Base_Test extends WC_Retailcrm_Test_Case_Helper
{
    protected $apiMock;
    protected $responseMockOrderMethods;
    protected $responseMockDeliveryTypes;
    protected $responseMockPaymentTypes;
    protected $responseMockStatuses;

    private $unit;

    public function setUp()
    {
        $this->apiMock = $this->getMockBuilder('\WC_Retailcrm_Proxy')
            ->disableOriginalConstructor()
            ->setMethods(array(
                'orderMethodsList',
                'deliveryTypesList',
                'paymentTypesList',
                'statusesList'
            ))
            ->getMock();

        $this->setMockOrderMethods();
        $this->setMockDeliveryTypes();
        $this->setMockPaymentTypes();
        $this->setMockStatuses();

        $_GET['page'] = 'wc-settings';
        $_GET['tab'] = 'integration';

        $this->setOptions('v5');
        $this->unit = new \WC_Retailcrm_Base($this->apiMock);
    }

    public function test_retailcrm_check_custom_file()
    {
        $file = \WC_Retailcrm_Base::checkCustomFile('ga');
        $this->assertInternalType('string', $file);
    }

    public function test_retailcrm_form_fields()
    {
        $this->assertInternalType('array', $this->unit->form_fields);
        $this->assertArrayHasKey('api_url', $this->unit->form_fields);
        $this->assertArrayHasKey('api_key', $this->unit->form_fields);

        foreach (get_post_statuses() as $key => $status) {
            $this->assertArrayHasKey('p_' . $key, $this->unit->form_fields);
        }

        $this->assertArrayHasKey('order_methods', $this->unit->form_fields);

        foreach (get_wc_shipping_methods() as $code => $value) {
            if (isset($value['enabled']) && $value['enabled'] == 'yes') {
                $this->assertArrayHasKey($code, $this->unit->form_fields);
            }
        }

        $wc_payment = WC_Payment_Gateways::instance();

        foreach ($wc_payment->get_available_payment_gateways() as $payment) {
            if (isset($payment->enabled) && $payment->enabled == 'yes') {
                $this->assertArrayHasKey($payment->id, $this->unit->form_fields);
            }
        }

        foreach (wc_get_order_statuses() as $idx => $name ) {
            $uid = str_replace('wc-', '', $idx);
            $this->assertArrayHasKey($uid, $this->unit->form_fields);
        }
    }

    private function getResponseOrderMethods()
    {
        return array(
            'success' => true,
            'orderMethods' => array(
                array(
                    'name' => 'orderMethod1',
                    'code' => 'orderMethod1',
                    'active' => true
                ),
                array(
                    'name' => 'orderMethod2',
                    'code' => 'orderMethod2',
                    'active' => true
                )
            )
        );
    }

    private function getResponseDeliveryTypes()
    {
        return array(
            'success' => true,
            'deliveryTypes' => array(
                array(
                    'name' => 'delivery1',
                    'code' => 'delivery1'
                ),
                array(
                    'name' => 'delivery2',
                    'code' => 'delivery2'
                )
            )
        );
    }

    private function getResponsePaymentTypes()
    {
        return array(
            'success' => true,
            'paymentTypes' => array(
                array(
                    'name' => 'payment1',
                    'code' => 'payment1'
                ),
                array(
                    'name' => 'payment2',
                    'code' => 'payment2'
                )
            )
        );
    }

    private function getResponseStatuses()
    {
        return array(
            'success' => true,
            'statuses' => array(
                array(
                    'name' => 'status1',
                    'code' => 'status1'
                ),
                array(
                    'name' => 'status2',
                    'code' => 'status2'
                )
            )
        );
    }

    private function setMockOrderMethods()
    {
        $this->responseMockOrderMethods = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
            ->disableOriginalConstructor()
            ->setMethods(array(
                'isSuccessful'
            ))
            ->getMock();

        $this->responseMockOrderMethods->expects($this->any())
            ->method('isSuccessful')
            ->willReturn(true);

        $this->responseMockOrderMethods->setResponse($this->getResponseOrderMethods());
        $this->apiMock->expects($this->any())
            ->method('orderMethodsList')
            ->willReturn($this->responseMockOrderMethods);
    }

    private function setMockDeliveryTypes()
    {
        $this->responseMockDeliveryTypes = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
            ->disableOriginalConstructor()
            ->setMethods(array(
                'isSuccessful'
            ))
            ->getMock();

        $this->responseMockDeliveryTypes->expects($this->any())
            ->method('isSuccessful')
            ->willReturn(true);

        $this->responseMockDeliveryTypes->setResponse($this->getResponseDeliveryTypes());
        $this->apiMock->expects($this->any())
            ->method('deliveryTypesList')
            ->willReturn($this->responseMockDeliveryTypes);
    }

    private function setMockPaymentTypes()
    {
        $this->responseMockPaymentTypes = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
            ->disableOriginalConstructor()
            ->setMethods(array(
                'isSuccessful'
            ))
            ->getMock();

        $this->responseMockPaymentTypes->expects($this->any())
            ->method('isSuccessful')
            ->willReturn(true);

        $this->responseMockPaymentTypes->setResponse($this->getResponsePaymentTypes());
        $this->apiMock->expects($this->any())
            ->method('paymentTypesList')
            ->willReturn($this->responseMockPaymentTypes);
    }

    private function setMockStatuses()
    {
        $this->responseMockStatuses = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
            ->disableOriginalConstructor()
            ->setMethods(array(
                'isSuccessful'
            ))
            ->getMock();

        $this->responseMockStatuses->expects($this->any())
            ->method('isSuccessful')
            ->willReturn(true);

        $this->responseMockStatuses->setResponse($this->getResponseStatuses());
        $this->apiMock->expects($this->any())
            ->method('statusesList')
            ->willReturn($this->responseMockStatuses);
    }
}
