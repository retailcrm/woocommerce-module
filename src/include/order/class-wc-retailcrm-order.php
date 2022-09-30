<?php

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Order - Build main data for CRM order.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_Order extends WC_Retailcrm_Abstracts_Data
{
    /** @var bool */
    public $is_new = true;

    protected $data = [
        'externalId' => 0,
        'status' => '',
        'number' => '',
        'createdAt' => '',
        'firstName' => '',
        'lastName' => '',
        'email' => '',
        'paymentType' => '',
        'customerComment' => '',
        'paymentStatus' => '',
        'phone' => '',
        'countryIso' => ''
    ];

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * WC_Retailcrm_Order constructor.
     *
     * @param array $settings
     */
    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param WC_Order $order
     *
     * @return self
     */
    public function build($order)
    {
        $firstName = $order->get_shipping_first_name();
        $lastName = $order->get_shipping_last_name();

        if (empty($firstName) && empty($lastName)) {
            $firstName = $order->get_billing_first_name();
            $lastName = $order->get_billing_last_name();
        }

        $dateCreate = $order->get_date_created();

        $data = [
            'externalId' => $order->get_id(),
            'createdAt' => !empty($dateCreate) ? $dateCreate->date('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => strtolower($order->get_billing_email()),
            'customerComment' => $order->get_customer_note(),
            'phone' => $order->get_billing_phone(),
            'countryIso' => $order->get_shipping_country()
        ];

        if ($data['countryIso'] == '--' || empty($data['countryIso'])) {
            $countries = new WC_Countries();
            $data['countryIso'] = $countries->get_base_country();
        }

        $this->setDataFields($data);
        $this->setNumber($order);

        if (isset($this->settings[$order->get_status()]) && 'not-upload' !== $this->settings[$order->get_status()]) {
            $this->setField('status', $this->settings[$order->get_status()]);
        }

        return $this;
    }

    /**
     * @param WC_Order $order
     */
    protected function setNumber($order)
    {
        if (isset($this->settings['update_number']) && $this->settings['update_number'] == WC_Retailcrm_Base::YES) {
            $this->setField('number', $order->get_order_number());
        } else {
            unset($this->data['number']);
        }
    }
}
