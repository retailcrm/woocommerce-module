<?php

if (!class_exists('WC_Retailcrm_Request')) {
    include_once(WC_Integration_Retailcrm::checkCustomFile('include/api/class-wc-retailcrm-request.php'));
}

if (!class_exists('WC_Retailcrm_Response')) {
    include_once(WC_Integration_Retailcrm::checkCustomFile('include/api/class-wc-retailcrm-response.php'));
}

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Client_V5 - Api Client V5 class.
 *
 * @category Integration
 * @package  WC_Retailcrm_Client
 * @author   RetailCRM <dev@retailcrm.ru>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://retailcrm.ru/docs/Developers/ApiVersion5
 */
class WC_Retailcrm_Client_V5
{
    protected $client;
    protected $unversionedClient;

    /**
     * Site code
     */
    protected $siteCode;

    /**
     * Client creating
     *
     * @param string $url    api url
     * @param string $apiKey api key
     * @param string $site   site code
     */
    public function __construct($url, $apiKey, $site = null)
    {
        if ('/' !== $url[strlen($url) - 1]) {
            $url .= '/';
        }

        $unversionedUrl = $url . 'api';
        $url .= 'api/v5';

        $this->client = new WC_Retailcrm_Request($url, ['apiKey' => $apiKey]);
        $this->unversionedClient = new WC_Retailcrm_Request($unversionedUrl, ['apiKey' => $apiKey]);
        $this->siteCode = $site;
    }

    /**
     * Returns api versions list
     *
     * @return WC_Retailcrm_Response
     */
    public function apiVersions()
    {
        return $this->unversionedClient->makeRequest('/api-versions', WC_Retailcrm_Request::METHOD_GET);
    }

    /**
     * Returns credentials list
     *
     * @return WC_Retailcrm_Response
     */
    public function credentials()
    {
        return $this->unversionedClient->makeRequest('/credentials', WC_Retailcrm_Request::METHOD_GET);
    }

    /**
     * Get cart by id or externalId
     *
     * @param $customerId
     * @param string $site
     * @param $by (default: 'externalId')
     *
     * @return WC_Retailcrm_Response
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     */
    public function cartGet($customerId, string $site, $by = 'externalId')
    {
        $this->checkIdParameter($by);

        if (empty($site)) {
            throw new InvalidArgumentException(
                'Site must be set'
            );
        }

        return $this->client->makeRequest(
            sprintf('/customer-interaction/%s/cart/%s', $site, $customerId),
            WC_Retailcrm_Request::METHOD_GET,
            ['by' => $by]
        );
    }

    /**
     * Create or update cart
     *
     * @param array $cart
     * @param string $site
     *
     * @return WC_Retailcrm_Response
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     */
    public function cartSet(array $cart, string $site)
    {
        if (empty($site)) {
            throw new InvalidArgumentException(
                'Site must be set'
            );
        }

        return $this->client->makeRequest(
            sprintf('/customer-interaction/%s/cart/set', $site),
            WC_Retailcrm_Request::METHOD_POST,
            ['cart' => json_encode($cart)]
        );
    }

    /**
     * Clear customer cart
     *
     * @param array $cart
     * @param string $site
     *
     * @return WC_Retailcrm_Response
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     */
    public function cartClear(array $cart, string $site)
    {
        if (empty($site)) {
            throw new InvalidArgumentException(
                'Site must be set'
            );
        }

        return $this->client->makeRequest(
            sprintf('/customer-interaction/%s/cart/clear', $site),
            WC_Retailcrm_Request::METHOD_POST,
            ['cart' => json_encode($cart)]
        );
    }

    /**
     * Returns filtered corporate customers list
     *
     * @param array $filter (default: array())
     * @param int   $page   (default: null)
     * @param int   $limit  (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function customersCorporateList(array $filter = [], $page = null, $limit = null)
    {
        $parameters = [];

        if (count($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest(
            '/customers-corporate',
            'GET',
            $parameters
        );
    }

    /**
     * Create a corporate customer
     *
     * @param array  $customerCorporate corporate customer data
     * @param string $site     (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function customersCorporateCreate(array $customerCorporate, $site = null)
    {
        if (! count($customerCorporate)) {
            throw new InvalidArgumentException(
                'Parameter `customerCorporate` must contains a data'
            );
        }

        return $this->client->makeRequest(
            '/customers-corporate/create',
            'POST',
            $this->fillSite($site, ['customerCorporate' => json_encode($customerCorporate)])
        );
    }

    /**
     * Save corporate customer IDs' (id and externalId) association in the CRM
     *
     * @param array $ids ids mapping
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function customersCorporateFixExternalIds(array $ids)
    {
        if (! count($ids)) {
            throw new InvalidArgumentException(
                'Method parameter must contains at least one IDs pair'
            );
        }

        return $this->client->makeRequest(
            '/customers-corporate/fix-external-ids',
            'POST',
            ['customersCorporate' => json_encode($ids)]
        );
    }

    /**
     * Get corporate customers history
     *
     * @param array $filter
     * @param int   $limit
     *
     * @return WC_Retailcrm_Response
     */
    public function customersCorporateHistory(array $filter = [], int $limit = 100)
    {
        return $this->client->makeRequest(
            '/customers-corporate/history',
            WC_Retailcrm_Request::METHOD_GET,
            ['limit' => $limit, 'filter' => $filter]
        );
    }

    /**
     * Returns filtered corporate customers notes list
     *
     * @param array $filter (default: array())
     * @param int   $page   (default: null)
     * @param int   $limit  (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function customersCorporateNotesList(array $filter = [], $page = null, $limit = null)
    {
        $parameters = [];

        if (count($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest(
            '/customers-corporate/notes',
            'GET',
            $parameters
        );
    }

    /**
     * Create corporate customer note
     *
     * @param array $note (default: array())
     * @param string $site     (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function customersCorporateNotesCreate($note, $site = null)
    {
        if (empty($note['customer']['id']) && empty($note['customer']['externalId'])) {
            throw new InvalidArgumentException(
                'Customer identifier must be set'
            );
        }

        return $this->client->makeRequest(
            '/customers-corporate/notes/create',
            'POST',
            $this->fillSite($site, ['note' => json_encode($note)])
        );
    }

    /**
     * Delete corporate customer note
     *
     * @param int $id
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function customersCorporateNotesDelete($id)
    {
        if (empty($id)) {
            throw new InvalidArgumentException(
                'Note id must be set'
            );
        }

        return $this->client->makeRequest(
            "/customers-corporate/notes/$id/delete",
            'POST'
        );
    }

    /**
     * Upload array of the corporate customers
     *
     * @param array  $customersCorporate array of corporate customers
     * @param string $site               (default: null)
     *
     * @return WC_Retailcrm_Response
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @throws InvalidArgumentException
     */
    public function customersCorporateUpload(array $customersCorporate, $site = null)
    {
        if (!count($customersCorporate)) {
            throw new InvalidArgumentException(
                'Parameter `customersCorporate` must contains array of the corporate customers'
            );
        }

        return $this->client->makeRequest(
            '/customers-corporate/upload',
            'POST',
            $this->fillSite($site, ['customersCorporate' => json_encode($customersCorporate)])
        );
    }

    /**
     * Get corporate customer by id or externalId
     *
     * @param string $id   corporate customer identifier
     * @param string $by   (default: 'externalId')
     * @param string $site (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function customersCorporateGet($id, $by = 'externalId', $site = null)
    {
        $this->checkIdParameter($by);

        return $this->client->makeRequest(
            "/customers-corporate/$id",
            'GET',
            $this->fillSite($site, ['by' => $by ])
        );
    }

    /**
     * Get corporate customer addresses by id or externalId
     *
     * @param string $id     corporate customer identifier
     * @param array  $filter (default: array())
     * @param int    $page   (default: null)
     * @param int    $limit  (default: null)
     * @param string $by     (default: 'externalId')
     * @param string $site   (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function customersCorporateAddresses(
        $id,
        array $filter = [],
        $page = null,
        $limit = null,
        $by = 'externalId',
        $site = null
    ) {
        $this->checkIdParameter($by);

        $parameters = ['by' => $by];

        if (count($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest(
            "/customers-corporate/$id/addresses",
            'GET',
            $this->fillSite($site, $parameters)
        );
    }

    /**
     * Create corporate customer address
     *
     * @param string $id       corporate customer identifier
     * @param array  $address  (default: array())
     * @param string $by       (default: 'externalId')
     * @param string $site     (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function customersCorporateAddressesCreate($id, array $address = [], $by = 'externalId', $site = null)
    {
        return $this->client->makeRequest(
            "/customers-corporate/$id/addresses/create",
            'POST',
            $this->fillSite($site, ['address' => json_encode($address), 'by' => $by ])
        );
    }

    /**
     * Edit corporate customer address
     *
     * @param string $customerId corporate customer identifier
     * @param string $addressId  corporate customer identifier
     * @param array  $address    (default: array())
     * @param string $customerBy (default: 'externalId')
     * @param string $addressBy  (default: 'externalId')
     * @param string $site       (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function customersCorporateAddressesEdit(
        $customerId,
        $addressId,
        array $address = [],
        $customerBy = 'externalId',
        $addressBy = 'externalId',
        $site = null
    ) {
        $addressFiltered = array_filter($address);

        if (
            (count(array_keys($addressFiltered)) <= 1)
            && (!isset($addressFiltered['text'])
                || (isset($addressFiltered['text']) && empty($addressFiltered['text']))
            )
        ) {
            throw new InvalidArgumentException(
                'Parameter `address` must contain address text or all other address field'
            );
        }

        return $this->client->makeRequest(
            "/customers-corporate/$customerId/addresses/$addressId/edit",
            'POST',
            $this->fillSite($site, [
                'address' => json_encode($address),
                'by' => $customerBy,
                'entityBy' => $addressBy
            ])
        );
    }

    /**
     * Get corporate customer companies by id or externalId
     *
     * @param string $id     corporate customer identifier
     * @param array  $filter (default: array())
     * @param int    $page   (default: null)
     * @param int    $limit  (default: null)
     * @param string $by     (default: 'externalId')
     * @param string $site   (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function customersCorporateCompanies(
        $id,
        array $filter = [],
        $page = null,
        $limit = null,
        $by = 'externalId',
        $site = null
    ) {
        $this->checkIdParameter($by);

        $parameters = ['by' => $by];

        if (count($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest(
            "/customers-corporate/$id/companies",
            'GET',
            $this->fillSite($site, $parameters)
        );
    }

    /**
     * Create corporate customer company
     *
     * @param string $id       corporate customer identifier
     * @param array  $company  (default: array())
     * @param string $by       (default: 'externalId')
     * @param string $site     (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function customersCorporateCompaniesCreate($id, array $company = [], $by = 'externalId', $site = null)
    {
        return $this->client->makeRequest(
            "/customers-corporate/$id/companies/create",
            'POST',
            $this->fillSite($site, ['company' => json_encode($company), 'by' => $by])
        );
    }

    /**
     * Edit corporate customer company
     *
     * @param string $customerId corporate customer identifier
     * @param string $companyId  corporate customer identifier
     * @param array  $company    (default: array())
     * @param string $customerBy (default: 'externalId')
     * @param string $companyBy  (default: 'externalId')
     * @param string $site       (default: null)
     *
     * @return WC_Retailcrm_Response
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     */
    public function customersCorporateCompaniesEdit(
        $customerId,
        $companyId,
        array $company = [],
        $customerBy = 'externalId',
        $companyBy = 'externalId',
        $site = null
    ) {
        return $this->client->makeRequest(
            "/customers-corporate/$customerId/companies/$companyId/edit",
            'POST',
            $this->fillSite($site, [
                'company' => json_encode($company),
                'by' => $customerBy,
                'entityBy' => $companyBy
            ])
        );
    }

    /**
     * Get corporate customer contacts by id or externalId
     *
     * @param string $id     corporate customer identifier
     * @param array  $filter (default: array())
     * @param int    $page   (default: null)
     * @param int    $limit  (default: null)
     * @param string $by     (default: 'externalId')
     * @param string $site   (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function customersCorporateContacts(
        $id,
        array $filter = [],
        $page = null,
        $limit = null,
        $by = 'externalId',
        $site = null
    ) {
        $this->checkIdParameter($by);

        $parameters = ['by' => $by];

        if (count($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest(
            "/customers-corporate/$id/contacts",
            'GET',
            $this->fillSite($site, $parameters)
        );
    }

    /**
     * Create corporate customer contact
     *
     * @param string $id      corporate customer identifier
     * @param array  $contact (default: array())
     * @param string $by      (default: 'externalId')
     * @param string $site    (default: null)
     *
     * @return WC_Retailcrm_Response
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @throws InvalidArgumentException
     */
    public function customersCorporateContactsCreate($id, array $contact = [], $by = 'externalId', $site = null)
    {
        return $this->client->makeRequest(
            "/customers-corporate/$id/contacts/create",
            'POST',
            $this->fillSite($site, ['contact' => json_encode($contact), 'by' => $by])
        );
    }

    /**
     * Edit corporate customer contact
     *
     * @param string $customerId corporate customer identifier
     * @param string $contactId  corporate customer identifier
     * @param array  $contact    (default: array())
     * @param string $customerBy (default: 'externalId')
     * @param string $contactBy  (default: 'externalId')
     * @param string $site       (default: null)
     *
     * @return WC_Retailcrm_Response
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     */
    public function customersCorporateContactsEdit(
        $customerId,
        $contactId,
        array $contact = [],
        $customerBy = 'externalId',
        $contactBy = 'externalId',
        $site = null
    ) {
        return $this->client->makeRequest(
            "/customers-corporate/$customerId/contacts/$contactId/edit",
            'POST',
            $this->fillSite($site, [
                'contact' => json_encode($contact),
                'by' => $customerBy,
                'entityBy' => $contactBy
            ])
        );
    }

    /**
     * Edit a corporate customer
     *
     * @param array  $customerCorporate corporate customer data
     * @param string $by       (default: 'externalId')
     * @param string $site     (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function customersCorporateEdit(array $customerCorporate, $by = 'externalId', $site = null)
    {
        if (!count($customerCorporate)) {
            throw new InvalidArgumentException(
                'Parameter `customerCorporate` must contains a data'
            );
        }
        $this->checkIdParameter($by);
        if (!array_key_exists($by, $customerCorporate)) {
            throw new InvalidArgumentException(
                sprintf('Corporate customer array must contain the "%s" parameter.', $by)
            );
        }

        return $this->client->makeRequest(
            sprintf('/customers-corporate/%s/edit', $customerCorporate[$by]),
            'POST',
            $this->fillSite(
                $site,
                ['customerCorporate' => json_encode($customerCorporate), 'by' => $by]
            )
        );
    }

    /**
     * Returns users list
     *
     * @param array $filter
     * @param null  $page
     * @param null  $limit
     *
     * @throws WC_Retailcrm_Exception_Json
     * @throws WC_Retailcrm_Exception_Curl
     * @throws InvalidArgumentException
     *
     * @return WC_Retailcrm_Response
     */
    public function usersList(array $filter = [], $page = null, $limit = null)
    {
        $parameters = [];

        if (count($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest(
            '/users',
            WC_Retailcrm_Request::METHOD_GET,
            $parameters
        );
    }

    /**
     * Returns user data
     *
     * @param int $id user ID
     *
     * @throws WC_Retailcrm_Exception_Json
     * @throws WC_Retailcrm_Exception_Curl
     * @throws InvalidArgumentException
     *
     * @return WC_Retailcrm_Response
     */
    public function usersGet($id)
    {
        return $this->client->makeRequest("/users/$id", WC_Retailcrm_Request::METHOD_GET);
    }

    /**
     * Change user status
     *
     * @param int $id     user ID
     * @param string  $status user status
     *
     * @return WC_Retailcrm_Response
     */
    public function usersStatus($id, $status)
    {
        $statuses = ['free', 'busy', 'dinner', 'break'];

        if (empty($status) || !in_array($status, $statuses)) {
            throw new InvalidArgumentException(
                'Parameter `status` must be not empty & must be equal one of these values: free|busy|dinner|break'
            );
        }

        return $this->client->makeRequest(
            "/users/$id/status",
            WC_Retailcrm_Request::METHOD_POST,
            ['status' => $status]
        );
    }

    /**
     * Get segments list
     *
     * @param array $filter
     * @param null  $limit
     * @param null  $page
     *
     * @return WC_Retailcrm_Response
     */
    public function segmentsList(array $filter = [], $limit = null, $page = null)
    {
        $parameters = [];

        if (count($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest(
            '/segments',
            WC_Retailcrm_Request::METHOD_GET,
            $parameters
        );
    }

    /**
     * Get custom fields list
     *
     * @param array $filter
     * @param null  $limit
     * @param null  $page
     *
     * @return WC_Retailcrm_Response
     */
    public function customFieldsList(array $filter = [], $limit = null, $page = null)
    {
        $parameters = [];

        if (count($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest(
            '/custom-fields',
            WC_Retailcrm_Request::METHOD_GET,
            $parameters
        );
    }

    /**
     * Create custom field
     *
     * @param $entity
     * @param $customField
     *
     * @return WC_Retailcrm_Response
     */
    public function customFieldsCreate($entity, $customField)
    {
        if (
            !count($customField) ||
            empty($customField['code']) ||
            empty($customField['name']) ||
            empty($customField['type'])
        ) {
            throw new InvalidArgumentException(
                'Parameter `customField` must contain a data & fields `code`, `name` & `type` must be set'
            );
        }

        if (empty($entity) || $entity != 'customer' || $entity != 'order') {
            throw new InvalidArgumentException(
                'Parameter `entity` must contain a data & value must be `order` or `customer`'
            );
        }

        return $this->client->makeRequest(
            "/custom-fields/$entity/create",
            WC_Retailcrm_Request::METHOD_POST,
            ['customField' => json_encode($customField)]
        );
    }

    /**
     * Edit custom field
     *
     * @param $entity
     * @param $customField
     *
     * @return WC_Retailcrm_Response
     */
    public function customFieldsEdit($entity, $customField)
    {
        if (!count($customField) || empty($customField['code'])) {
            throw new InvalidArgumentException(
                'Parameter `customField` must contain a data & fields `code` must be set'
            );
        }

        if (empty($entity) || $entity != 'customer' || $entity != 'order') {
            throw new InvalidArgumentException(
                'Parameter `entity` must contain a data & value must be `order` or `customer`'
            );
        }

        return $this->client->makeRequest(
            "/custom-fields/$entity/edit/{$customField['code']}",
            WC_Retailcrm_Request::METHOD_POST,
            ['customField' => json_encode($customField)]
        );
    }

    /**
     * Get custom field
     *
     * @param $entity
     * @param $code
     *
     * @return WC_Retailcrm_Response
     */
    public function customFieldsGet($entity, $code)
    {
        if (empty($code)) {
            throw new InvalidArgumentException(
                'Parameter `code` must be not empty'
            );
        }

        if (empty($entity) || !in_array($entity, ['customer', 'order', 'customer_corporate', 'company'])) {
            throw new InvalidArgumentException(
                sprintf(
                    'Parameter `entity` must contain a data & value must be %s',
                    '`order`, `customer`, `customer_corporate` or `company`'
                )
            );
        }

        return $this->client->makeRequest(
            "/custom-fields/$entity/$code",
            WC_Retailcrm_Request::METHOD_GET
        );
    }

    /**
     * Get custom dictionaries list
     *
     * @param array $filter
     * @param null  $limit
     * @param null  $page
     *
     * @return WC_Retailcrm_Response
     */
    public function customDictionariesList(array $filter = [], $limit = null, $page = null)
    {
        $parameters = [];

        if (count($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest(
            '/custom-fields/dictionaries',
            WC_Retailcrm_Request::METHOD_GET,
            $parameters
        );
    }

    /**
     * Create custom dictionary
     *
     * @param $customDictionary
     *
     * @return WC_Retailcrm_Response
     */
    public function customDictionariesCreate($customDictionary)
    {
        if (
            !count($customDictionary) ||
            empty($customDictionary['code']) ||
            empty($customDictionary['elements'])
        ) {
            throw new InvalidArgumentException(
                'Parameter `dictionary` must contain a data & fields `code` & `elemets` must be set'
            );
        }

        return $this->client->makeRequest(
            "/custom-fields/dictionaries/{$customDictionary['code']}/create",
            WC_Retailcrm_Request::METHOD_POST,
            ['customDictionary' => json_encode($customDictionary)]
        );
    }

    /**
     * Edit custom dictionary
     *
     * @param $customDictionary
     *
     * @return WC_Retailcrm_Response
     */
    public function customDictionariesEdit($customDictionary)
    {
        if (
            !count($customDictionary) ||
            empty($customDictionary['code']) ||
            empty($customDictionary['elements'])
        ) {
            throw new InvalidArgumentException(
                'Parameter `dictionary` must contain a data & fields `code` & `elemets` must be set'
            );
        }

        return $this->client->makeRequest(
            "/custom-fields/dictionaries/{$customDictionary['code']}/edit",
            WC_Retailcrm_Request::METHOD_POST,
            ['customDictionary' => json_encode($customDictionary)]
        );
    }

    /**
     * Get custom dictionary
     *
     * @param $code
     *
     * @return WC_Retailcrm_Response
     */
    public function customDictionariesGet($code)
    {
        if (empty($code)) {
            throw new InvalidArgumentException(
                'Parameter `code` must be not empty'
            );
        }

        return $this->client->makeRequest(
            "/custom-fields/dictionaries/$code",
            WC_Retailcrm_Request::METHOD_GET
        );
    }

    /**
     * Returns filtered orders list
     *
     * @param array $filter (default: array())
     * @param int   $page   (default: null)
     * @param int   $limit  (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function ordersList(array $filter = [], $page = null, $limit = null)
    {
        $parameters = [];

        if (count($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest(
            '/orders',
            WC_Retailcrm_Request::METHOD_GET,
            $parameters
        );
    }

    /**
     * Create an order
     *
     * @param array  $order order data
     * @param string $site  (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function ordersCreate(array $order, $site = null)
    {
        if (!count($order)) {
            throw new InvalidArgumentException(
                'Parameter `order` must contains a data'
            );
        }

        return $this->client->makeRequest(
            '/orders/create',
            WC_Retailcrm_Request::METHOD_POST,
            $this->fillSite($site, ['order' => json_encode($order)])
        );
    }

    /**
     * Save order IDs' (id and externalId) association in the CRM
     *
     * @param array $ids order identificators
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function ordersFixExternalIds(array $ids)
    {
        if (! count($ids)) {
            throw new InvalidArgumentException(
                'Method parameter must contains at least one IDs pair'
            );
        }

        return $this->client->makeRequest(
            '/orders/fix-external-ids',
            WC_Retailcrm_Request::METHOD_POST,
            [
                'orders' => json_encode($ids)
            ]
        );
    }

    /**
     * Returns statuses of the orders
     *
     * @param array $ids         (default: array())
     * @param array $externalIds (default: array())
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function ordersStatuses(array $ids = [], array $externalIds = [])
    {
        $parameters = [];

        if (count($ids)) {
            $parameters['ids'] = $ids;
        }
        if (count($externalIds)) {
            $parameters['externalIds'] = $externalIds;
        }

        return $this->client->makeRequest(
            '/orders/statuses',
            WC_Retailcrm_Request::METHOD_GET,
            $parameters
        );
    }

    /**
     * Upload array of the orders
     *
     * @param array  $orders array of orders
     * @param string $site   (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function ordersUpload(array $orders, $site = null)
    {
        if (!count($orders)) {
            throw new InvalidArgumentException(
                'Parameter `orders` must contains array of the orders'
            );
        }

        return $this->client->makeRequest(
            '/orders/upload',
            WC_Retailcrm_Request::METHOD_POST,
            $this->fillSite($site, ['orders' => json_encode($orders)])
        );
    }

    /**
     * Get order by id or externalId
     *
     * @param string $id   order identificator
     * @param string $by   (default: 'externalId')
     * @param string $site (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function ordersGet($id, $by = 'externalId', $site = null)
    {
        $this->checkIdParameter($by);

        return $this->client->makeRequest(
            "/orders/$id",
            WC_Retailcrm_Request::METHOD_GET,
            $this->fillSite($site, ['by' => $by])
        );
    }

    /**
     * Edit a order
     *
     * @param array  $order order data
     * @param string $by    (default: 'externalId')
     * @param string $site  (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function ordersEdit(array $order, $by = 'externalId', $site = null)
    {
        if (!count($order)) {
            throw new InvalidArgumentException(
                'Parameter `order` must contains a data'
            );
        }

        $this->checkIdParameter($by);

        if (!array_key_exists($by, $order)) {
            throw new InvalidArgumentException(
                sprintf('Order array must contain the "%s" parameter.', $by)
            );
        }

        return $this->client->makeRequest(
            sprintf('/orders/%s/edit', $order[$by]),
            WC_Retailcrm_Request::METHOD_POST,
            $this->fillSite(
                $site,
                ['order' => json_encode($order), 'by' => $by]
            )
        );
    }

    /**
     * Get orders history
     *
     * @param array $filter
     * @param int   $limit
     *
     * @return WC_Retailcrm_Response
     */
    public function ordersHistory(array $filter = [], int $limit = 100)
    {
        return $this->client->makeRequest(
            '/orders/history',
            WC_Retailcrm_Request::METHOD_GET,
            ['limit' => $limit, 'filter' => $filter]
        );
    }

    /**
     * Combine orders
     *
     * @param string $technique
     * @param array  $order
     * @param array  $resultOrder
     *
     * @return WC_Retailcrm_Response
     */
    public function ordersCombine($order, $resultOrder, $technique = 'ours')
    {
        $techniques = ['ours', 'summ', 'theirs'];

        if (!count($order) || !count($resultOrder)) {
            throw new InvalidArgumentException(
                'Parameters `order` & `resultOrder` must contains a data'
            );
        }

        if (!in_array($technique, $techniques)) {
            throw new InvalidArgumentException(
                'Parameter `technique` must be on of ours|summ|theirs'
            );
        }

        return $this->client->makeRequest(
            '/orders/combine',
            WC_Retailcrm_Request::METHOD_POST,
            [
                'technique' => $technique,
                'order' => json_encode($order),
                'resultOrder' => json_encode($resultOrder)
            ]
        );
    }

    /**
     * Create an order payment
     *
     * @param array $payment order data
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function ordersPaymentCreate(array $payment)
    {
        if (!count($payment)) {
            throw new InvalidArgumentException(
                'Parameter `payment` must contains a data'
            );
        }

        return $this->client->makeRequest(
            '/orders/payments/create',
            WC_Retailcrm_Request::METHOD_POST,
            ['payment' => json_encode($payment)]
        );
    }

    /**
     * Edit an order payment
     *
     * @param array  $payment order data
     * @param string $by      by key
     * @param null   $site    site code
     *
     * @return WC_Retailcrm_Response
     */
    public function ordersPaymentEdit(array $payment, $by = 'externalId', $site = null)
    {
        if (!count($payment)) {
            throw new InvalidArgumentException(
                'Parameter `payment` must contains a data'
            );
        }

        $this->checkIdParameter($by);

        if (!array_key_exists($by, $payment)) {
            throw new InvalidArgumentException(
                sprintf('Order array must contain the "%s" parameter.', $by)
            );
        }

        return $this->client->makeRequest(
            sprintf('/orders/payments/%s/edit', $payment[$by]),
            WC_Retailcrm_Request::METHOD_POST,
            $this->fillSite(
                $site,
                ['payment' => json_encode($payment), 'by' => $by]
            )
        );
    }

    /**
     * Edit an order payment
     *
     * @param string $id payment id
     *
     * @return WC_Retailcrm_Response
     */
    public function ordersPaymentDelete($id)
    {
        if (!$id) {
            throw new InvalidArgumentException(
                'Parameter `id` must be set'
            );
        }

        return $this->client->makeRequest(
            sprintf('/orders/payments/%s/delete', $id),
            WC_Retailcrm_Request::METHOD_POST
        );
    }

    /**
     * Returns filtered customers list
     *
     * @param array $filter (default: array())
     * @param int   $page   (default: null)
     * @param int   $limit  (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function customersList(array $filter = [], $page = null, $limit = null)
    {
        $parameters = [];

        if (count($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest(
            '/customers',
            WC_Retailcrm_Request::METHOD_GET,
            $parameters
        );
    }

    /**
     * Create a customer
     *
     * @param array  $customer customer data
     * @param string $site     (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function customersCreate(array $customer, $site = null)
    {
        if (! count($customer)) {
            throw new InvalidArgumentException(
                'Parameter `customer` must contains a data'
            );
        }

        return $this->client->makeRequest(
            '/customers/create',
            WC_Retailcrm_Request::METHOD_POST,
            $this->fillSite($site, ['customer' => json_encode($customer)])
        );
    }

    /**
     * Save customer IDs' (id and externalId) association in the CRM
     *
     * @param array $ids ids mapping
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function customersFixExternalIds(array $ids)
    {
        if (! count($ids)) {
            throw new InvalidArgumentException(
                'Method parameter must contains at least one IDs pair'
            );
        }

        return $this->client->makeRequest(
            '/customers/fix-external-ids',
            WC_Retailcrm_Request::METHOD_POST,
            ['customers' => json_encode($ids)]
        );
    }

    /**
     * Upload array of the customers
     *
     * @param array  $customers array of customers
     * @param string $site      (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function customersUpload(array $customers, $site = null)
    {
        if (! count($customers)) {
            throw new InvalidArgumentException(
                'Parameter `customers` must contains array of the customers'
            );
        }

        return $this->client->makeRequest(
            '/customers/upload',
            WC_Retailcrm_Request::METHOD_POST,
            $this->fillSite($site, ['customers' => json_encode($customers)])
        );
    }

    /**
     * Get customer by id or externalId
     *
     * @param string $id   customer identificator
     * @param string $by   (default: 'externalId')
     * @param string $site (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function customersGet($id, $by = 'externalId', $site = null)
    {
        $this->checkIdParameter($by);

        return $this->client->makeRequest(
            "/customers/$id",
            WC_Retailcrm_Request::METHOD_GET,
            $this->fillSite($site, ['by' => $by])
        );
    }

    /**
     * Edit a customer
     *
     * @param array  $customer customer data
     * @param string $by       (default: 'externalId')
     * @param string $site     (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function customersEdit(array $customer, $by = 'externalId', $site = null)
    {
        if (!count($customer)) {
            throw new InvalidArgumentException(
                'Parameter `customer` must contains a data'
            );
        }

        $this->checkIdParameter($by);

        if (!array_key_exists($by, $customer)) {
            throw new InvalidArgumentException(
                sprintf('Customer array must contain the "%s" parameter.', $by)
            );
        }

        return $this->client->makeRequest(
            sprintf('/customers/%s/edit', $customer[$by]),
            WC_Retailcrm_Request::METHOD_POST,
            $this->fillSite(
                $site,
                ['customer' => json_encode($customer), 'by' => $by]
            )
        );
    }

    /**
     * Get customers history
     *
     * @param array $filter
     * @param int   $limit
     *
     * @return WC_Retailcrm_Response
     */
    public function customersHistory(array $filter = [], int $limit = 100)
    {
        return $this->client->makeRequest(
            '/customers/history',
            WC_Retailcrm_Request::METHOD_GET,
            ['limit' => $limit, 'filter' => $filter]
        );
    }

    /**
     * Combine customers
     *
     * @param array $customers
     * @param array $resultCustomer
     *
     * @return WC_Retailcrm_Response
     */
    public function customersCombine(array $customers, $resultCustomer)
    {

        if (!count($customers) || !count($resultCustomer)) {
            throw new InvalidArgumentException(
                'Parameters `customers` & `resultCustomer` must contains a data'
            );
        }

        return $this->client->makeRequest(
            '/customers/combine',
            WC_Retailcrm_Request::METHOD_POST,
            [
                'customers' => json_encode($customers),
                'resultCustomer' => json_encode($resultCustomer)
            ]
        );
    }

    /**
     * Returns filtered customers notes list
     *
     * @param array $filter (default: array())
     * @param int   $page   (default: null)
     * @param int   $limit  (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function customersNotesList(array $filter = [], $page = null, $limit = null)
    {
        $parameters = [];
        if (count($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }
        return $this->client->makeRequest(
            '/customers/notes',
            WC_Retailcrm_Request::METHOD_GET,
            $parameters
        );
    }

    /**
     * Create customer note
     *
     * @param array $note (default: array())
     * @param string $site     (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function customersNotesCreate($note, $site = null)
    {
        if (empty($note['customer']['id']) && empty($note['customer']['externalId'])) {
            throw new InvalidArgumentException(
                'Customer identifier must be set'
            );
        }
        return $this->client->makeRequest(
            '/customers/notes/create',
            WC_Retailcrm_Request::METHOD_POST,
            $this->fillSite($site, ['note' => json_encode($note)])
        );
    }

    /**
     * Delete customer note
     *
     * @param int $id
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function customersNotesDelete($id)
    {
        if (empty($id)) {
            throw new InvalidArgumentException(
                'Note id must be set'
            );
        }
        return $this->client->makeRequest(
            "/customers/notes/$id/delete",
            WC_Retailcrm_Request::METHOD_POST
        );
    }

    /**
     * Get orders assembly list
     *
     * @param array $filter (default: array())
     * @param int   $page   (default: null)
     * @param int   $limit  (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function ordersPacksList(array $filter = [], $page = null, $limit = null)
    {
        $parameters = [];

        if (count($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest(
            '/orders/packs',
            WC_Retailcrm_Request::METHOD_GET,
            $parameters
        );
    }

    /**
     * Create orders assembly
     *
     * @param array  $pack pack data
     * @param string $site (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function ordersPacksCreate(array $pack, $site = null)
    {
        if (!count($pack)) {
            throw new InvalidArgumentException(
                'Parameter `pack` must contains a data'
            );
        }

        return $this->client->makeRequest(
            '/orders/packs/create',
            WC_Retailcrm_Request::METHOD_POST,
            $this->fillSite($site, ['pack' => json_encode($pack)])
        );
    }

    /**
     * Get orders assembly history
     *
     * @param array $filter (default: array())
     * @param int $limit  (default: null)
     *
     * @return WC_Retailcrm_Response
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @throws InvalidArgumentException
     */
    public function ordersPacksHistory(array $filter = [], int $limit = 100)
    {
        return $this->client->makeRequest(
            '/orders/packs/history',
            WC_Retailcrm_Request::METHOD_GET,
            ['limit' => $limit, 'filter' => $filter]
        );
    }

    /**
     * Get orders assembly by id
     *
     * @param string $id pack identificator
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function ordersPacksGet($id)
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Parameter `id` must be set');
        }

        return $this->client->makeRequest(
            "/orders/packs/$id",
            WC_Retailcrm_Request::METHOD_GET
        );
    }

    /**
     * Delete orders assembly by id
     *
     * @param string $id pack identificator
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function ordersPacksDelete($id)
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Parameter `id` must be set');
        }

        return $this->client->makeRequest(
            sprintf('/orders/packs/%s/delete', $id),
            WC_Retailcrm_Request::METHOD_POST
        );
    }

    /**
     * Edit orders assembly
     *
     * @param array  $pack pack data
     * @param string $site (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function ordersPacksEdit(array $pack, $site = null)
    {
        if (!count($pack) || empty($pack['id'])) {
            throw new InvalidArgumentException(
                'Parameter `pack` must contains a data & pack `id` must be set'
            );
        }

        return $this->client->makeRequest(
            sprintf('/orders/packs/%s/edit', $pack['id']),
            WC_Retailcrm_Request::METHOD_POST,
            $this->fillSite($site, ['pack' => json_encode($pack)])
        );
    }

    /**
     * Get tasks list
     *
     * @param array $filter
     * @param null  $limit
     * @param null  $page
     *
     * @return WC_Retailcrm_Response
     */
    public function tasksList(array $filter = [], $limit = null, $page = null)
    {
        $parameters = [];

        if (count($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest(
            '/tasks',
            WC_Retailcrm_Request::METHOD_GET,
            $parameters
        );
    }

    /**
     * Create task
     *
     * @param array $task
     * @param null  $site
     *
     * @return WC_Retailcrm_Response
     *
     */
    public function tasksCreate($task, $site = null)
    {
        if (!count($task)) {
            throw new InvalidArgumentException(
                'Parameter `task` must contain a data'
            );
        }

        return $this->client->makeRequest(
            '/tasks/create',
            WC_Retailcrm_Request::METHOD_POST,
            $this->fillSite(
                $site,
                ['task' => json_encode($task)]
            )
        );
    }

    /**
     * Edit task
     *
     * @param array $task
     * @param null  $site
     *
     * @return WC_Retailcrm_Response
     *
     */
    public function tasksEdit($task, $site = null)
    {
        if (!count($task)) {
            throw new InvalidArgumentException(
                'Parameter `task` must contain a data'
            );
        }

        return $this->client->makeRequest(
            "/tasks/{$task['id']}/edit",
            WC_Retailcrm_Request::METHOD_POST,
            $this->fillSite(
                $site,
                ['task' => json_encode($task)]
            )
        );
    }

    /**
     * Get custom dictionary
     *
     * @param $id
     *
     * @return WC_Retailcrm_Response
     */
    public function tasksGet($id)
    {
        if (empty($id)) {
            throw new InvalidArgumentException(
                'Parameter `id` must be not empty'
            );
        }

        return $this->client->makeRequest(
            "/tasks/$id",
            WC_Retailcrm_Request::METHOD_GET
        );
    }

    /**
     * Get products groups
     *
     * @param array $filter (default: array())
     * @param int   $page   (default: null)
     * @param int   $limit  (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function storeProductsGroups(array $filter = [], $page = null, $limit = null)
    {
        $parameters = [];

        if (count($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest(
            '/store/product-groups',
            WC_Retailcrm_Request::METHOD_GET,
            $parameters
        );
    }

    /**
     * Get purchace prices & stock balance
     *
     * @param array $filter (default: array())
     * @param int   $page   (default: null)
     * @param int   $limit  (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function storeInventories(array $filter = [], $page = null, $limit = null)
    {
        $parameters = [];

        if (count($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest(
            '/store/inventories',
            WC_Retailcrm_Request::METHOD_GET,
            $parameters
        );
    }

    /**
     * Get store settings
     *
     * @param string $code get settings code
     *
     * @return WC_Retailcrm_Response
     * @throws WC_Retailcrm_Exception_Json
     * @throws WC_Retailcrm_Exception_Curl
     * @throws InvalidArgumentException
     *
     * @return WC_Retailcrm_Response
     */
    public function storeSettingsGet($code)
    {
        if (empty($code)) {
            throw new InvalidArgumentException('Parameter `code` must be set');
        }

        return $this->client->makeRequest(
            "/store/setting/$code",
            WC_Retailcrm_Request::METHOD_GET
        );
    }

    /**
     * Edit store configuration
     *
     * @param array $configuration
     *
     * @throws WC_Retailcrm_Exception_Json
     * @throws WC_Retailcrm_Exception_Curl
     * @throws InvalidArgumentException
     *
     * @return WC_Retailcrm_Response
     */
    public function storeSettingsEdit(array $configuration)
    {
        if (!count($configuration) || empty($configuration['code'])) {
            throw new InvalidArgumentException(
                'Parameter `configuration` must contains a data & configuration `code` must be set'
            );
        }

        return $this->client->makeRequest(
            sprintf('/store/setting/%s/edit', $configuration['code']),
            WC_Retailcrm_Request::METHOD_POST,
            $configuration
        );
    }

    /**
     * Upload store inventories
     *
     * @param array  $offers offers data
     * @param string $site   (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function storeInventoriesUpload(array $offers, $site = null)
    {
        if (!count($offers)) {
            throw new InvalidArgumentException(
                'Parameter `offers` must contains array of the offers'
            );
        }

        return $this->client->makeRequest(
            '/store/inventories/upload',
            WC_Retailcrm_Request::METHOD_POST,
            $this->fillSite($site, ['offers' => json_encode($offers)])
        );
    }

    /**
     * Upload store prices
     *
     * @param array  $prices prices data
     * @param string $site   default: null)
     *
     * @throws \InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function storePricesUpload(array $prices, $site = null)
    {
        if (!count($prices)) {
            throw new \InvalidArgumentException(
                'Parameter `prices` must contains array of the prices'
            );
        }

        return $this->client->makeRequest(
            '/store/prices/upload',
            WC_Retailcrm_Request::METHOD_POST,
            $this->fillSite($site, array('prices' => json_encode($prices)))
        );
    }

    /**
     * Get products
     *
     * @param array $filter (default: array())
     * @param int   $page   (default: null)
     * @param int   $limit  (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function storeProducts(array $filter = [], $page = null, $limit = null)
    {
        $parameters = [];

        if (count($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest(
            '/store/products',
            WC_Retailcrm_Request::METHOD_GET,
            $parameters
        );
    }

    /**
     * Get delivery settings
     *
     * @param string $code
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function deliverySettingsGet($code)
    {
        if (empty($code)) {
            throw new InvalidArgumentException('Parameter `code` must be set');
        }

        return $this->client->makeRequest(
            "/delivery/generic/setting/$code",
            WC_Retailcrm_Request::METHOD_GET
        );
    }

    /**
     * Edit delivery configuration
     *
     * @param array $configuration
     *
     * @throws WC_Retailcrm_Exception_Json
     * @throws WC_Retailcrm_Exception_Curl
     * @throws InvalidArgumentException
     *
     * @return WC_Retailcrm_Response
     */
    public function deliverySettingsEdit(array $configuration)
    {
        if (!count($configuration) || empty($configuration['code'])) {
            throw new InvalidArgumentException(
                'Parameter `configuration` must contains a data & configuration `code` must be set'
            );
        }

        return $this->client->makeRequest(
            sprintf('/delivery/generic/setting/%s/edit', $configuration['code']),
            WC_Retailcrm_Request::METHOD_POST,
            ['configuration' => json_encode($configuration)]
        );
    }

    /**
     * Delivery tracking update
     *
     * @param string $code
     * @param array  $statusUpdate
     *
     * @throws WC_Retailcrm_Exception_Json
     * @throws WC_Retailcrm_Exception_Curl
     * @throws InvalidArgumentException
     *
     * @return WC_Retailcrm_Response
     */
    public function deliveryTracking($code, array $statusUpdate)
    {
        if (empty($code)) {
            throw new InvalidArgumentException('Parameter `code` must be set');
        }

        if (!count($statusUpdate)) {
            throw new InvalidArgumentException(
                'Parameter `statusUpdate` must contains a data'
            );
        }

        return $this->client->makeRequest(
            sprintf('/delivery/generic/%s/tracking', $code),
            WC_Retailcrm_Request::METHOD_POST,
            $statusUpdate
        );
    }

    /**
     * Returns available county list
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function countriesList()
    {
        return $this->client->makeRequest(
            '/reference/countries',
            WC_Retailcrm_Request::METHOD_GET
        );
    }

    /**
     * Returns deliveryServices list
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function deliveryServicesList()
    {
        return $this->client->makeRequest(
            '/reference/delivery-services',
            WC_Retailcrm_Request::METHOD_GET
        );
    }

    /**
     * Edit deliveryService
     *
     * @param array $data delivery service data
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function deliveryServicesEdit(array $data)
    {
        if (!array_key_exists('code', $data)) {
            throw new InvalidArgumentException(
                'Data must contain "code" parameter.'
            );
        }

        return $this->client->makeRequest(
            sprintf('/reference/delivery-services/%s/edit', $data['code']),
            WC_Retailcrm_Request::METHOD_POST,
            ['deliveryService' => json_encode($data)]
        );
    }

    /**
     * Returns deliveryTypes list
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function deliveryTypesList()
    {
        return $this->client->makeRequest(
            '/reference/delivery-types',
            WC_Retailcrm_Request::METHOD_GET
        );
    }

    /**
     * Edit deliveryType
     *
     * @param array $data delivery type data
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function deliveryTypesEdit(array $data)
    {
        if (!array_key_exists('code', $data)) {
            throw new InvalidArgumentException(
                'Data must contain "code" parameter.'
            );
        }

        return $this->client->makeRequest(
            sprintf('/reference/delivery-types/%s/edit', $data['code']),
            WC_Retailcrm_Request::METHOD_POST,
            ['deliveryType' => json_encode($data)]
        );
    }

    /**
     * Returns orderMethods list
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function orderMethodsList()
    {
        return $this->client->makeRequest(
            '/reference/order-methods',
            WC_Retailcrm_Request::METHOD_GET
        );
    }

    /**
     * Edit orderMethod
     *
     * @param array $data order method data
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function orderMethodsEdit(array $data)
    {
        if (!array_key_exists('code', $data)) {
            throw new InvalidArgumentException(
                'Data must contain "code" parameter.'
            );
        }

        return $this->client->makeRequest(
            sprintf('/reference/order-methods/%s/edit', $data['code']),
            WC_Retailcrm_Request::METHOD_POST,
            ['orderMethod' => json_encode($data)]
        );
    }

    /**
     * Returns orderTypes list
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function orderTypesList()
    {
        return $this->client->makeRequest(
            '/reference/order-types',
            WC_Retailcrm_Request::METHOD_GET
        );
    }

    /**
     * Edit orderType
     *
     * @param array $data order type data
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function orderTypesEdit(array $data)
    {
        if (!array_key_exists('code', $data)) {
            throw new InvalidArgumentException(
                'Data must contain "code" parameter.'
            );
        }

        return $this->client->makeRequest(
            sprintf('/reference/order-types/%s/edit', $data['code']),
            WC_Retailcrm_Request::METHOD_POST,
            ['orderType' => json_encode($data)]
        );
    }

    /**
     * Returns paymentStatuses list
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function paymentStatusesList()
    {
        return $this->client->makeRequest(
            '/reference/payment-statuses',
            WC_Retailcrm_Request::METHOD_GET
        );
    }

    /**
     * Edit paymentStatus
     *
     * @param array $data payment status data
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function paymentStatusesEdit(array $data)
    {
        if (!array_key_exists('code', $data)) {
            throw new InvalidArgumentException(
                'Data must contain "code" parameter.'
            );
        }

        return $this->client->makeRequest(
            sprintf('/reference/payment-statuses/%s/edit', $data['code']),
            WC_Retailcrm_Request::METHOD_POST,
            ['paymentStatus' => json_encode($data)]
        );
    }

    /**
     * Returns paymentTypes list
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function paymentTypesList()
    {
        return $this->client->makeRequest(
            '/reference/payment-types',
            WC_Retailcrm_Request::METHOD_GET
        );
    }

    /**
     * Edit paymentType
     *
     * @param array $data payment type data
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function paymentTypesEdit(array $data)
    {
        if (!array_key_exists('code', $data)) {
            throw new InvalidArgumentException(
                'Data must contain "code" parameter.'
            );
        }

        return $this->client->makeRequest(
            sprintf('/reference/payment-types/%s/edit', $data['code']),
            WC_Retailcrm_Request::METHOD_POST,
            ['paymentType' => json_encode($data)]
        );
    }

    /**
     * Returns productStatuses list
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function productStatusesList()
    {
        return $this->client->makeRequest(
            '/reference/product-statuses',
            WC_Retailcrm_Request::METHOD_GET
        );
    }

    /**
     * Edit productStatus
     *
     * @param array $data product status data
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function productStatusesEdit(array $data)
    {
        if (!array_key_exists('code', $data)) {
            throw new InvalidArgumentException(
                'Data must contain "code" parameter.'
            );
        }

        return $this->client->makeRequest(
            sprintf('/reference/product-statuses/%s/edit', $data['code']),
            WC_Retailcrm_Request::METHOD_POST,
            ['productStatus' => json_encode($data)]
        );
    }

    /**
     * Returns sites list
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function sitesList()
    {
        return $this->client->makeRequest(
            '/reference/sites',
            WC_Retailcrm_Request::METHOD_GET
        );
    }

    /**
     * Edit site
     *
     * @param array $data site data
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function sitesEdit(array $data)
    {
        if (!array_key_exists('code', $data)) {
            throw new InvalidArgumentException(
                'Data must contain "code" parameter.'
            );
        }

        return $this->client->makeRequest(
            sprintf('/reference/sites/%s/edit', $data['code']),
            WC_Retailcrm_Request::METHOD_POST,
            ['site' => json_encode($data)]
        );
    }

    /**
     * Returns statusGroups list
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function statusGroupsList()
    {
        return $this->client->makeRequest(
            '/reference/status-groups',
            WC_Retailcrm_Request::METHOD_GET
        );
    }

    /**
     * Returns statuses list
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function statusesList()
    {
        return $this->client->makeRequest(
            '/reference/statuses',
            WC_Retailcrm_Request::METHOD_GET
        );
    }

    /**
     * Edit order status
     *
     * @param array $data status data
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function statusesEdit(array $data)
    {
        if (!array_key_exists('code', $data)) {
            throw new InvalidArgumentException(
                'Data must contain "code" parameter.'
            );
        }

        return $this->client->makeRequest(
            sprintf('/reference/statuses/%s/edit', $data['code']),
            WC_Retailcrm_Request::METHOD_POST,
            ['status' => json_encode($data)]
        );
    }

    /**
     * Returns stores list
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function storesList()
    {
        return $this->client->makeRequest(
            '/reference/stores',
            WC_Retailcrm_Request::METHOD_GET
        );
    }

    /**
     * Edit store
     *
     * @param array $data site data
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function storesEdit(array $data)
    {
        if (!array_key_exists('code', $data)) {
            throw new InvalidArgumentException(
                'Data must contain "code" parameter.'
            );
        }

        if (!array_key_exists('name', $data)) {
            throw new InvalidArgumentException(
                'Data must contain "name" parameter.'
            );
        }

        return $this->client->makeRequest(
            sprintf('/reference/stores/%s/edit', $data['code']),
            WC_Retailcrm_Request::METHOD_POST,
            ['store' => json_encode($data)]
        );
    }

    /**
     * Get telephony settings
     *
     * @param string $code
     *
     * @throws WC_Retailcrm_Exception_Json
     * @throws WC_Retailcrm_Exception_Curl
     * @throws InvalidArgumentException
     *
     * @return WC_Retailcrm_Response
     */
    public function telephonySettingsGet($code)
    {
        if (empty($code)) {
            throw new InvalidArgumentException('Parameter `code` must be set');
        }

        return $this->client->makeRequest(
            "/telephony/setting/$code",
            WC_Retailcrm_Request::METHOD_GET
        );
    }

    /**
     * Edit telephony settings
     *
     * @param string  $code        symbolic code
     * @param string  $clientId    client id
     * @param bool    $active      telephony activity
     * @param mixed   $name        service name
     * @param mixed   $makeCallUrl service init url
     * @param mixed   $image       service logo url(svg file)
     *
     * @param array   $additionalCodes
     * @param array   $externalPhones
     * @param bool    $allowEdit
     * @param bool    $inputEventSupported
     * @param bool    $outputEventSupported
     * @param bool    $hangupEventSupported
     * @param bool    $changeUserStatusUrl
     *
     * @return WC_Retailcrm_Response
     */
    public function telephonySettingsEdit(
        $code,
        $clientId,
        $active = false,
        $name = false,
        $makeCallUrl = false,
        $image = false,
        $additionalCodes = [],
        $externalPhones = [],
        $allowEdit = false,
        $inputEventSupported = false,
        $outputEventSupported = false,
        $hangupEventSupported = false,
        $changeUserStatusUrl = false
    ) {
        if (!isset($code)) {
            throw new InvalidArgumentException('Code must be set');
        }

        $parameters['code'] = $code;

        if (!isset($clientId)) {
            throw new InvalidArgumentException('client id must be set');
        }

        $parameters['clientId'] = $clientId;

        if (!isset($active)) {
            $parameters['active'] = false;
        } else {
            $parameters['active'] = $active;
        }

        if (!isset($name)) {
            throw new InvalidArgumentException('name must be set');
        }

        if (isset($name)) {
            $parameters['name'] = $name;
        }

        if (isset($makeCallUrl)) {
            $parameters['makeCallUrl'] = $makeCallUrl;
        }

        if (isset($image)) {
            $parameters['image'] = $image;
        }

        if (isset($additionalCodes)) {
            $parameters['additionalCodes'] = $additionalCodes;
        }

        if (isset($externalPhones)) {
            $parameters['externalPhones'] = $externalPhones;
        }

        if (isset($allowEdit)) {
            $parameters['allowEdit'] = $allowEdit;
        }

        if (isset($inputEventSupported)) {
            $parameters['inputEventSupported'] = $inputEventSupported;
        }

        if (isset($outputEventSupported)) {
            $parameters['outputEventSupported'] = $outputEventSupported;
        }

        if (isset($hangupEventSupported)) {
            $parameters['hangupEventSupported'] = $hangupEventSupported;
        }

        if (isset($changeUserStatusUrl)) {
            $parameters['changeUserStatusUrl'] = $changeUserStatusUrl;
        }

        return $this->client->makeRequest(
            "/telephony/setting/$code/edit",
            WC_Retailcrm_Request::METHOD_POST,
            ['configuration' => json_encode($parameters)]
        );
    }

    /**
     * Call event
     *
     * @param string $phone phone number
     * @param string $type  call type
     * @param array  $codes
     * @param string $hangupStatus
     * @param string $externalPhone
     * @param array  $webAnalyticsData
     *
     * @return WC_Retailcrm_Response
     * @internal param string $code additional phone code
     * @internal param string $status call status
     *
     */
    public function telephonyCallEvent(
        $phone,
        $type,
        $codes,
        $hangupStatus,
        $externalPhone = null,
        $webAnalyticsData = []
    ) {
        if (!isset($phone)) {
            throw new InvalidArgumentException('Phone number must be set');
        }

        if (!isset($type)) {
            throw new InvalidArgumentException('Type must be set (in|out|hangup)');
        }

        if (empty($codes)) {
            throw new InvalidArgumentException('Codes array must be set');
        }

        $parameters['phone'] = $phone;
        $parameters['type'] = $type;
        $parameters['codes'] = $codes;
        $parameters['hangupStatus'] = $hangupStatus;
        $parameters['callExternalId'] = $externalPhone;
        $parameters['webAnalyticsData'] = $webAnalyticsData;


        return $this->client->makeRequest(
            '/telephony/call/event',
            WC_Retailcrm_Request::METHOD_POST,
            ['event' => json_encode($parameters)]
        );
    }

    /**
     * Upload calls
     *
     * @param array $calls calls data
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function telephonyCallsUpload(array $calls)
    {
        if (!count($calls)) {
            throw new InvalidArgumentException(
                'Parameter `calls` must contains array of the calls'
            );
        }

        return $this->client->makeRequest(
            '/telephony/calls/upload',
            WC_Retailcrm_Request::METHOD_POST,
            ['calls' => json_encode($calls)]
        );
    }

    /**
     * Get call manager
     *
     * @param string $phone   phone number
     * @param bool   $details detailed information
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function telephonyCallManager($phone, $details)
    {
        if (!isset($phone)) {
            throw new InvalidArgumentException('Phone number must be set');
        }

        $parameters['phone'] = $phone;
        $parameters['details'] = isset($details) ? $details : 0;

        return $this->client->makeRequest(
            '/telephony/manager',
            WC_Retailcrm_Request::METHOD_GET,
            $parameters
        );
    }

    /**
     * Edit module configuration
     *
     * @param array $configuration
     *
     * @throws WC_Retailcrm_Exception_Json
     * @throws WC_Retailcrm_Exception_Curl
     * @throws InvalidArgumentException
     *
     * @return WC_Retailcrm_Response
     */
    public function integrationModulesEdit(array $configuration)
    {
        if (!count($configuration) || empty($configuration['code'])) {
            throw new InvalidArgumentException(
                'Parameter `configuration` must contains a data & configuration `code` must be set'
            );
        }

        $code = $configuration['code'];

        return $this->client->makeRequest(
            "/integration-modules/$code/edit",
            WC_Retailcrm_Request::METHOD_POST,
            ['integrationModule' => json_encode($configuration)]
        );
    }

    /**
     * Update CRM basic statistic
     *
     * @throws InvalidArgumentException
     * @throws WC_Retailcrm_Exception_Curl
     * @throws WC_Retailcrm_Exception_Json
     *
     * @return WC_Retailcrm_Response
     */
    public function statisticUpdate()
    {
        return $this->client->makeRequest(
            '/statistic/update',
            WC_Retailcrm_Request::METHOD_GET
        );
    }

    /**
     * Return current site
     *
     * @return string
     */
    public function getSite()
    {
        return $this->siteCode;
    }

    /**
     * getSingleSiteForKey
     *
     * @return string|bool
     */
    public function getSingleSiteForKey()
    {
        $site = $this->getSite();

        if (!empty($site)) {
            return $this->getSite();
        }

        $response = $this->credentials();

        if (
            $response instanceof WC_Retailcrm_Response
            && $response->offsetExists('sitesAvailable')
            && is_array($response['sitesAvailable'])
            && !empty($response['sitesAvailable'])
        ) {
            $this->siteCode = $response['sitesAvailable'][0];
        }

        return $this->getSite();
    }

    /**
     * Set site
     *
     * @param string $site site code
     *
     * @return void
     */
    public function setSite($site)
    {
        $this->siteCode = $site;
    }

    /**
     * Check ID parameter
     *
     * @param string $by identify by
     *
     * @throws InvalidArgumentException
     *
     * @return bool
     */
    protected function checkIdParameter($by)
    {
        $allowedForBy = [
            'externalId',
            'id'
        ];

        if (!in_array($by, $allowedForBy, false)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Value "%s" for "by" param is not valid. Allowed values are %s.',
                    $by,
                    implode(', ', $allowedForBy)
                )
            );
        }

        return true;
    }

    /**
     * Fill params by site value
     *
     * @param string $site   site code
     * @param array  $params input parameters
     *
     * @return array
     */
    protected function fillSite($site, array $params)
    {
        if ($site) {
            $params['site'] = $site;
        } elseif ($this->siteCode) {
            $params['site'] = $this->siteCode;
        }

        return $params;
    }
}
