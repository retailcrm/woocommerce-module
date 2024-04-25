<?php

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

    /** @dataProvider dataCheckUser */
    public function testCheckUser($responseApiMethod, $wcUserType, $throwMessage)
    {
        $this->setResponseMock();
        $this->setApiMock('customersGet', $responseApiMethod);

        $validator = new WC_Retailcrm_Loyalty_Validator($this->apiMock);
        $method = $this->getPrivateMethod('checkUser', $validator);

        $wcUserId = $wcUserType === 'individual' ? $this->individualClient->get_id() : $this->corpClient->get_id();

        try {
            $result = $method->invokeArgs($validator, [$wcUserId]);

            if ($throwMessage) {
                $this->fail('ValidatorException was not thrown');
            } else {
                $this->assertEquals($responseApiMethod['customer']['id'], $result['id']);
            }
        } catch (ValidatorException $exception) {
            $this->assertEquals($throwMessage, $exception->getMessage());
        }
    }

    /** @dataProvider dataLoyaltyAccount */
    public function testGetLoyaltyAccount($responseMock, $throwMessage)
    {
        $this->setResponseMock($responseMock);
        $this->setApiMock('getLoyaltyAccountList', $this->responseMock);

        $validator = new WC_Retailcrm_Loyalty_Validator($this->apiMock);
        $method = $this->getPrivateMethod('getLoyaltyAccount', $validator);

        try {
            $result = $method->invokeArgs($validator, [777]);

            if ($throwMessage) {
                $this->fail('ValidatorException was not thrown');
            } else {
                $this->assertNotEmpty($result);
            }
        } catch (ValidatorException $exception) {
            $this->assertEquals($throwMessage, $exception->getMessage());
        }

       /* $this->setResponseMock(['success' => true, 'loyaltyAccounts' => []]);
        $this->setApiMock('getLoyaltyAccountList', $this->responseMock);

        $validator = new WC_Retailcrm_Loyalty_Validator($this->apiMock);
        $method = $this->getPrivateMethod('getLoyaltyAccount', $validator);

        try {
            $method->invokeArgs($validator, [777]);

            $this->fail('ValidatorException was not thrown');
        } catch (ValidatorException $exception) {
            $this->assertEquals('No active participation in the loyalty program was detected', $exception->getMessage());
        }

        $this->setResponseMock(['success' => true, 'loyaltyAccounts' => [['active' => true, 'amount' => 0, 'level' => ['type' => 'bonus_converting']]]]);
        $this->setApiMock('getLoyaltyAccountList', $this->responseMock);

        $validator = new WC_Retailcrm_Loyalty_Validator($this->apiMock);
        $method = $this->getPrivateMethod('getLoyaltyAccount', $validator);

        try {
            $method->invokeArgs($validator, [777]);

            $this->fail('ValidatorException was not thrown');
        } catch (ValidatorException $exception) {
            $this->assertEquals('No bonuses for debiting', $exception->getMessage());
        }

        $this->setResponseMock(['success' => true, 'loyaltyAccounts' => [['active' => true, 'amount' => 0, 'level' => ['type' => 'discount']]]]);
        $this->setApiMock('getLoyaltyAccountList', $this->responseMock);

        $validator = new WC_Retailcrm_Loyalty_Validator($this->apiMock);
        $method = $this->getPrivateMethod('getLoyaltyAccount', $validator);
        $result = $method->invokeArgs($validator, [777]);

        $this->assertNotEmpty($result);

        $this->setResponseMock(['success' => true, 'loyaltyAccounts' => [['active' => true, 'amount' => 100, 'level' => ['type' => 'bonus_converting']]]]);
        $this->setApiMock('getLoyaltyAccountList', $this->responseMock);

        $validator = new WC_Retailcrm_Loyalty_Validator($this->apiMock);
        $method = $this->getPrivateMethod('getLoyaltyAccount', $validator);
        $result = $method->invokeArgs($validator, [777]);

        $this->assertNotEmpty($result);*/

    }


    public function dataCheckUser()
    {
        return [
            [
                'responseApiMethod' => [],
                'wcUserType' => 'individual',
                'throwMessage' => 'User not found in the system'
            ],
            [
                'responseApiMethod' => ['customer' => ['id' => 1]],
                'wcUserType' => 'corp',
                'throwMessage' => 'This user is a corporate person'
            ],
            [
                'responseApiMethod' => ['customer' => ['id' => 1]],
                'wcUserType' => 'individual',
                'throwMessage' => null
            ],
        ];
    }

    public function dataLoyaltyAccount()
    {
        return [
            [
                'responseMock' => ['success' => true],
                'throwMessage' => 'Error when searching for participation in loyalty programs'
            ],
            [
                'responseMock' => ['success' => true, 'loyaltyAccounts' => []],
                'throwMessage' => 'No active participation in the loyalty program was detected'
            ],
            [
                'responseMock' => ['success' => true, 'loyaltyAccounts' => [['active' => true, 'amount' => 0, 'level' => ['type' => 'bonus_converting']]]],
                'throwMessage' => 'No bonuses for debiting'
            ],
            [
                'responseMock' => ['success' => true, 'loyaltyAccounts' => [['active' => true, 'amount' => 0, 'level' => ['type' => 'discount']]]],
                'throwMessage' => null
            ],
            [
                'responseMock' => ['success' => true, 'loyaltyAccounts' => [['active' => true, 'amount' => 100, 'level' => ['type' => 'bonus_converting']]]],
                'throwMessage' => null
            ],
        ];
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
