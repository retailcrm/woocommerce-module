<?php
/**
 * PHP version 5.3
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */

if (!class_exists('WC_Retailcrm_Customer_Corporate_Response')) :
/**
 * Class WC_Retailcrm_Customer_Corporate_Response
 */
class WC_Retailcrm_Customer_Corporate_Response
{
    /**
     * @var int $corporateId
     */
    private $corporateId;

    /**
     * @var string $corporateExternalId
     */
    private $corporateExternalId;

    /**
     * @var int $addressId
     */
    private $addressId;

    /**
     * @var int $companyId
     */
    private $companyId;

    /**
     * @var int $contactId
     */
    private $contactId;

    /**
     * @var int $contactExternalId
     */
    private $contactExternalId;

    /**
     * WC_Retailcrm_Customer_Corporate_Response constructor.
     *
     * @param int    $corporateId
     * @param string $corporateExternalId
     * @param int    $addressId
     * @param int    $companyId
     * @param int    $contactId
     * @param string $contactExternalId
     */
    public function __construct(
        $corporateId,
        $corporateExternalId,
        $addressId,
        $companyId,
        $contactId,
        $contactExternalId
    ) {
        $this->corporateId = $corporateId;
        $this->corporateExternalId = $corporateExternalId;
        $this->addressId = $addressId;
        $this->companyId = $companyId;
        $this->contactId = $contactId;
        $this->contactExternalId = $contactExternalId;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->corporateId;
    }

    /**
     * @return string
     */
    public function getExternalId()
    {
        return $this->corporateExternalId;
    }

    /**
     * @return int
     */
    public function getAddressId()
    {
        return $this->addressId;
    }

    /**
     * @return int
     */
    public function getCompanyId()
    {
        return $this->companyId;
    }

    /**
     * @return int
     */
    public function getContactId()
    {
        return $this->contactId;
    }

    /**
     * @return int
     */
    public function getContactExternalId()
    {
        return $this->contactExternalId;
    }
}

endif;
