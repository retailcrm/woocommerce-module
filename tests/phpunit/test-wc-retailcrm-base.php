<?php

class WC_Retailcrm_Base_Test extends WC_Unit_Test_Case
{
    protected $apiMock;
    private $unit;

    public function setUp()
    {
        $this->apiMock = $this->getMockBuilder('\WC_Retailcrm_Proxy')
            ->disableOriginalConstructor()
            ->getMock();

        $this->unit = new \WC_Retailcrm_Base();
        $this->unit->apiClient = $this->apiMock;
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
        $this->assertArrayHasKey('api_version', $this->unit->form_fields);
    }
}
