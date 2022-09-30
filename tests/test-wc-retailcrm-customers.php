<?php

use datasets\DataCustomersRetailCrm;

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Customers_Test - Testing WC_Retailcrm_Customers.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_Customers_Test extends WC_Retailcrm_Test_Case_Helper
{
    protected $apiMock;
    protected $responseMock;
    protected $customer;

    public function setUp()
    {
        $this->responseMock = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
                                   ->disableOriginalConstructor()
                                   ->setMethods([
                                       'isSuccessful',
                                       'offsetExists'
                                   ])
                                   ->getMock();

        $this->responseMock->setResponse(['id' => 1]);

        $this->apiMock = $this->getMockBuilder('\WC_Retailcrm_Proxy')
                              ->disableOriginalConstructor()
                              ->setMethods([
                                  'ordersGet',
                                  'ordersCreate',
                                  'ordersEdit',
                                  'customersGet',
                                  'customersCreate',
                                  'customersEdit',
                                  'getCorporateEnabled',
                                  'customersCorporateCreate',
                                  'customersCorporateAddressesCreate',
                                  'customersCorporateCompaniesCreate',
                                  'getSingleSiteForKey',
                                  'customersCorporateAddresses',
                                  'customersList'
                              ])
                              ->getMock();

        $this->setMockResponse($this->responseMock, 'isSuccessful', true);
        $this->setMockResponse($this->responseMock, 'offsetExists', true);
        $this->setMockResponse($this->apiMock, 'getCorporateEnabled', true);
        $this->setMockResponse($this->apiMock, 'getSingleSiteForKey', 'test');
        $this->setMockResponse($this->apiMock, 'customersCreate', $this->responseMock);
        $this->setMockResponse($this->apiMock, 'customersCorporateCreate', $this->responseMock);
        $this->setMockResponse($this->apiMock, 'customersCorporateAddressesCreate', $this->responseMock);
        $this->setMockResponse($this->apiMock, 'customersCorporateCompaniesCreate', $this->responseMock);
        $this->setMockResponse($this->apiMock, 'customersCorporateCreate', true);

        $this->customer = new WC_Customer();
        $this->customer->set_first_name('Tester');
        $this->customer->set_last_name('Tester');
        $this->customer->set_email(uniqid(md5(date('Y-m-d H:i:s'))) . '@mail.com');
        $this->customer->set_billing_email($this->customer->get_email());
        $this->customer->set_password('password');
        $this->customer->set_billing_phone('89000000000');
        $this->customer->set_billing_company('test_company');
        $this->customer->set_billing_state('test_state');
        $this->customer->set_billing_postcode('123456');
        $this->customer->set_billing_city('test_city');
        $this->customer->set_billing_address_1('test_address_line');
        $this->customer->set_date_created(date('Y-m-d H:i:s'));
        $this->customer->save();

        update_user_meta($this->customer->get_id(), 'woo_customer', 'test_custom_fields');
    }

    /**
     * @param retailcrm
     *
     * @dataProvider dataProviderApiClient
     */
    public function test_wc_customer_get($retailcrm)
    {
        $wc_customer = new WC_Customer($this->customer->get_id());
        $crmCustomer = $this->getRetailcrmCustomer($retailcrm);

        $this->assertEquals($wc_customer, $crmCustomer->wcCustomerGet($this->customer->get_id()));
    }

    /**
     * @param $retailcrm
     *
     * @dataProvider dataProviderApiClient
     */
    public function test_create_customer($retailcrm)
    {
        $crmCustomer  = $this->getRetailcrmCustomer($retailcrm);

        $id       = $crmCustomer->createCustomer($this->customer->get_id());
        $customer = $crmCustomer->getCustomer();

        if ($retailcrm) {
            $this->assertArrayHasKey('firstName', $customer);
            $this->assertArrayHasKey('createdAt', $customer);
            $this->assertArrayHasKey('email', $customer);
            $this->assertNotEmpty($customer['externalId']);
            $this->assertNotEmpty($customer['createdAt']);
            $this->assertNotEmpty($customer['firstName']);
            $this->assertNotEmpty($customer['email']);
            $this->assertEquals($customer['firstName'], $this->customer->get_first_name());
            $this->assertEquals($customer['email'], $this->customer->get_email());
            $this->assertEquals($customer['customFields']['crm_customer'], 'test_custom_fields');
        } else {
            $this->assertEquals(null, $id);
            $this->assertEquals([], $customer);
        }
    }

    /**
     * @param $retailcrm
     *
     * @dataProvider dataProviderApiClient
     */
    public function test_create_customer_registration($retailcrm)
    {
        if ($retailcrm) {
            $this->buildResponseCustomersList($retailcrm, DataCustomersRetailCrm::getEmptyCustomersList());
        }

        $crmCustomer = $this->getRetailcrmCustomer($retailcrm);

        $id       = $crmCustomer->registerCustomer($this->customer->get_id());
        $customer = $crmCustomer->getCustomer();

        if ($retailcrm) {
            $this->assertArrayHasKey('firstName', $customer);
            $this->assertArrayHasKey('createdAt', $customer);
            $this->assertArrayHasKey('email', $customer);
            $this->assertNotEmpty($customer['externalId']);
            $this->assertNotEmpty($customer['createdAt']);
            $this->assertNotEmpty($customer['firstName']);
            $this->assertNotEmpty($customer['email']);
            $this->assertEquals($customer['firstName'], $this->customer->get_first_name());
            $this->assertEquals($customer['email'], $this->customer->get_email());
            $this->assertEquals($customer['customFields']['crm_customer'], 'test_custom_fields');
        } else {
            $this->assertEquals(null, $id);
            $this->assertEquals([], $customer);
        }
    }

    public function test_empty_customer_registration()
    {
        $emptyCustomer = new WC_Customer();
        $crmCustomer   = $this->getRetailcrmCustomer($this->apiMock);

        $this->assertEquals(null, $crmCustomer->registerCustomer($emptyCustomer->get_id()));
    }

    public function test_create_customer_empty_data()
    {
        $crmCustomer = $this->getRetailcrmCustomer($this->apiMock);

        $id       = $crmCustomer->createCustomer(null);
        $customer = $crmCustomer->getCustomer();

        $this->assertEquals(null, $id);
        $this->assertEquals([], $customer);
    }

    /**
     * @param $retailcrm
     *
     * @dataProvider dataProviderApiClient
     */
    public function test_update_customer_registration($retailcrm)
    {
        if ($retailcrm) {
            $this->buildResponseCustomersList($retailcrm, DataCustomersRetailCrm::getCustomersList());
        }

        $crmCustomer = $this->getRetailcrmCustomer($retailcrm);
        $wcCustomer  = $crmCustomer->registerCustomer($this->customer->get_id());
        $customer    = $crmCustomer->getCustomer();

        if ($retailcrm) {
            $this->assertArrayHasKey('externalId', $customer);
            $this->assertArrayHasKey('firstName', $customer);
            $this->assertArrayHasKey('createdAt', $customer);
            $this->assertArrayHasKey('email', $customer);
            $this->assertNotEmpty($customer['externalId']);
            $this->assertNotEmpty($customer['createdAt']);
            $this->assertNotEmpty($customer['firstName']);
            $this->assertNotEmpty($customer['email']);
            $this->assertEquals($customer['customFields']['crm_customer'], 'test_custom_fields');
        } else {
            $this->assertEquals(null, $wcCustomer);
            $this->assertEquals([], $customer);
        }
    }

    /**
     * @param $retailcrm
     *
     * @dataProvider dataProviderApiClient
     * @throws Exception
     */
    public function test_update_customer($retailcrm)
    {
        $crmCustomer = $this->getRetailcrmCustomer($retailcrm);
        $wcCustomer  = $crmCustomer->updateCustomer($this->customer->get_id());
        $customer    = $crmCustomer->getCustomer();

        if ($retailcrm) {
            $this->assertInstanceOf('WC_Customer', $wcCustomer);
            $this->assertArrayHasKey('externalId', $customer);
            $this->assertArrayHasKey('firstName', $customer);
            $this->assertArrayHasKey('createdAt', $customer);
            $this->assertArrayHasKey('email', $customer);
            $this->assertNotEmpty($customer['externalId']);
            $this->assertNotEmpty($customer['createdAt']);
            $this->assertNotEmpty($customer['firstName']);
            $this->assertNotEmpty($customer['email']);
            $this->assertEquals($customer['customFields']['crm_customer'], 'test_custom_fields');
        } else {
            $this->assertEquals(null, $wcCustomer);
            $this->assertEquals([], $customer);
        }
    }

    /**
     * @param $retailcrm
     *
     * @dataProvider dataProviderApiClient
     */
    public function test_update_customer_by_id($retailcrm)
    {
        $crmCustomer = $this->getRetailcrmCustomer($retailcrm);
        $wcCustomer  = $crmCustomer->updateCustomerById($this->customer->get_id(), '12345');
        $customer    = $crmCustomer->getCustomer();

        if ($retailcrm) {
            $this->assertInstanceOf('WC_Customer', $wcCustomer);
            $this->assertArrayHasKey('externalId', $customer);
            $this->assertArrayHasKey('firstName', $customer);
            $this->assertArrayHasKey('createdAt', $customer);
            $this->assertArrayHasKey('email', $customer);
            $this->assertNotEmpty($customer['externalId']);
            $this->assertNotEmpty($customer['createdAt']);
            $this->assertNotEmpty($customer['firstName']);
            $this->assertNotEmpty($customer['email']);
        } else {
            $this->assertEquals(null, $wcCustomer);
            $this->assertEquals([], $customer);
        }
    }

    /**
     * @param $retailcrm
     *
     * @dataProvider dataProviderApiClient
     */
    public function test_is_corparate_enabled($retailcrm)
    {
        $crmCustomer = $this->getRetailcrmCustomer($retailcrm);
        $isCorporate = $crmCustomer->isCorporateEnabled();

        if ($retailcrm) {
            $this->assertEquals(true, $isCorporate);
        } else {
            $this->assertEquals(false, $isCorporate);
        }
    }

    /**
     * @param $retailcrm
     *
     * @dataProvider dataProviderApiClient
     */
    public function test_create_customer_corporate($retailcrm)
    {
        $crmCustomer = $this->getRetailcrmCustomer($retailcrm);

        $id       = $crmCustomer->createCorporateCustomerForOrder(777, $this->customer->get_id(), new WC_Order());
        $customer = $crmCustomer->getCorporateCustomer();

        if ($retailcrm) {
            $this->assertArrayHasKey('customerContacts', $customer);

            foreach ($customer['customerContacts'] as $customerCorporate) {
                $this->assertArrayHasKey('isMain', $customerCorporate);
                $this->assertArrayHasKey('customer', $customerCorporate);
                $this->assertEquals($customerCorporate['isMain'], true);
                $this->assertEquals($customerCorporate['customer']['id'], 777);
            }
        } else {
            $this->assertEquals(null, $id);
            $this->assertEquals([], $customer);
        }
    }

    public function test_create_customer_corporate_empty_data()
    {
        $crmCustomer = $this->getRetailcrmCustomer($this->apiMock);

        $id       = $crmCustomer->createCorporateCustomerForOrder(777, null, new WC_Order());
        $customer = $crmCustomer->getCorporateCustomer();

        $this->assertEquals(null, $id);
        $this->assertEquals([], $customer);
    }

    public function test_fill_corporate_address()
    {
        $crmCustomer = $this->getRetailcrmCustomer($this->apiMock);

        // Mock response for get customer address
        $responseCustomerAddress = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
                                        ->disableOriginalConstructor()
                                        ->setMethods(['isSuccessful'])
                                        ->getMock();

        $this->setMockResponse($responseCustomerAddress, 'isSuccessful', true);
        $responseCustomerAddress->setResponse(DataCustomersRetailCrm::getCustomerAddress());

        //Set responseCustomerAddress mock for apiMock
        $this->setMockResponse($this->apiMock, 'customersCorporateAddresses', $responseCustomerAddress);

        $addressFound = $crmCustomer->fillCorporateAddress($this->customer->get_id(), $this->customer);

        $this->assertEquals(true, $addressFound);

        $this->customer->set_billing_state('test_state123');
        $this->customer->set_billing_postcode('123456123');
        $this->customer->set_billing_city('test_city123');
        $this->customer->set_billing_address_1('test_address_line123');

        $addressFound = $crmCustomer->fillCorporateAddress($this->customer->get_id(), $this->customer);

        $this->assertEquals(false, $addressFound);
    }

    public function dataProviderApiClient()
    {
        $this->setUp();

        return [
            [
                'retailcrm' => $this->apiMock
            ],
            [
                'retailcrm' => false
            ]
        ];
    }

    /**
     * @param $retailcrm
     *
     * @return WC_Retailcrm_Customers
     */
    private function getRetailcrmCustomer($retailcrm)
    {
        return new WC_Retailcrm_Customers(
            $retailcrm,
            $this->getOptions(),
            new WC_Retailcrm_Customer_Address()
        );
    }

    /**
     * @param $retailcrm
     * @param $response
     *
     * @return void
     */
    private function buildResponseCustomersList($retailcrm, $response)
    {
        // Mock response for get customers list
        $responseCustomersList = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
                                      ->disableOriginalConstructor()
                                      ->setMethods(['isSuccessful'])
                                      ->getMock();

        $this->setMockResponse($responseCustomersList, 'isSuccessful', true);
        $responseCustomersList->setResponse($response);

        //Set responseCustomersList mock for apiMock
        $this->setMockResponse($retailcrm, 'customersList', $responseCustomersList);
    }
}
