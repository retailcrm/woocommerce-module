<?php

use datasets\DataLoyaltyRetailCrm;

class WC_Retailcrm_Loyalty_Validator_Test extends WC_Retailcrm_Test_Case_Helper
{
    protected $responseMock;
    protected $apiMock;
    protected $individualClient;
    protected $corpClient;

    public function setUp()
    {
        $this->individualClient = new WC_Customer();
        $this->individualClient->set_first_name('Test');
        $this->individualClient->set_last_name('Test');
        $this->individualClient->set_email(uniqid(md5(date('Y-m-d H:i:s'))) . '@mail.com');
        $this->individualClient->set_billing_email( $this->individualClient->get_email());
        $this->individualClient->set_password('password');
        $this->individualClient->set_billing_phone('89000000000');
        $this->individualClient->set_date_created(date('Y-m-d H:i:s'));
        $this->individualClient->save();

        $this->corpClient = new WC_Customer();
        $this->corpClient->set_first_name('Test');
        $this->corpClient->set_last_name('Test');
        $this->corpClient->set_email(uniqid(md5(date('Y-m-d H:i:s'))) . '@mail.com');
        $this->corpClient->set_billing_email($this->corpClient->get_email());
        $this->corpClient->set_password('password');
        $this->corpClient->set_billing_phone('89000000000');
        $this->corpClient->set_date_created(date('Y-m-d H:i:s'));
        $this->corpClient->set_shipping_company('TEST COMPANY');
        $this->corpClient->save();
    }

    /** @dataProvider datasets\DataLoyaltyRetailCrm::dataCheckUser() */
    public function testCheckUser($responseApiMethod, $wcUserType, $throwMessage, $isCorpActive)
    {
        $this->setResponseMock();
        $this->setApiMock('customersGet', $responseApiMethod);

        $validator = new WC_Retailcrm_Loyalty_Validator($this->apiMock, $isCorpActive);
        $method = $this->getPrivateMethod('checkUser', $validator);

        $wcUserId = $wcUserType === 'individual' ? $this->individualClient->get_id() : $this->corpClient->get_id();

        try {
            $method->invokeArgs($validator, [$wcUserId]);

            if ($throwMessage) {
                $this->fail('ValidatorException was not thrown');
            } else {
                $this->assertTrue(true);
            }
        } catch (ValidatorException $exception) {
            $this->assertEquals($throwMessage, $exception->getMessage());
        }
    }

    /** @dataProvider datasets\DataLoyaltyRetailCrm::dataLoyaltyAccount() */
    public function testGetLoyaltyAccount($responseMock, $throwMessage)
    {
        $this->setResponseMock($responseMock);
        $this->setApiMock('getLoyaltyAccountList', $this->responseMock);

        $validator = new WC_Retailcrm_Loyalty_Validator($this->apiMock, true);
        $method = $this->getPrivateMethod('checkLoyaltyAccount', $validator);

        try {
            $method->invokeArgs($validator, [777]);

            if ($throwMessage) {
                $this->fail('ValidatorException was not thrown');
            } else {
                $this->assertTrue(true);
            }
        } catch (ValidatorException $exception) {
            $this->assertEquals($throwMessage, $exception->getMessage());
        }
    }

    /** @dataProvider datasets\DataLoyaltyRetailCrm::dataCheckActiveLoyalty() */
    public function testCheckActivateLoyalty($responseMock, $throwMessage)
    {
        $this->setResponseMock($responseMock);
        $this->setApiMock('getLoyalty', $this->responseMock);

        $validator = new WC_Retailcrm_Loyalty_Validator($this->apiMock, true);
        $method = $this->getPrivateMethod('checkActiveLoyalty', $validator);

        try {
            $method->invokeArgs($validator, [1]);

            if ($throwMessage) {
                $this->fail('ValidatorException was not thrown');
            } else {
                $this->assertTrue(true);
            }
        } catch (ValidatorException $exception) {
            $this->assertEquals($throwMessage, $exception->getMessage());
        }
    }

    private function setResponseMock($response = ['success' => true])
    {
        $this->responseMock = $this->getMockBuilder('\WC_Retailcrm_Response_Helper')
            ->disableOriginalConstructor()
            ->setMethods(['isSuccessful'])
            ->getMock()
        ;

        $this->responseMock->setResponse($response);
        $this->setMockResponse($this->responseMock, 'isSuccessful', true);
    }

    private function setApiMock($method, $response)
    {
        $this->apiMock = $this->getMockBuilder('\WC_Retailcrm_Client_V5')
            ->disableOriginalConstructor()
            ->setMethods([$method])
            ->getMock()
        ;
        $this->setMockResponse($this->apiMock, $method, $response);
    }

    private function getPrivateMethod($method, $class)
    {
        $reflection = new ReflectionClass($class);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method;
    }

    public function tearDown()
    {
        $this->individualClient->delete();
        $this->corpClient->delete();
    }
}
