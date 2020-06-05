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

class WC_Retailcrm_Order extends WC_Retailcrm_Abstracts_Data
{
    /** @var bool */
    public $is_new = true;

    protected $filter_name = 'order';

    protected $data = array(
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
    );

    /**
     * @var array
     */
    protected $settings = array();

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
        $data = array(
            'externalId' => $order->get_id(),
            'createdAt' => $order->get_date_created()->date('Y-m-d H:i:s'),
            'firstName' => $order->get_shipping_first_name(),
            'lastName' => $order->get_shipping_last_name(),
            'email' => $order->get_billing_email(),
            'customerComment' => $order->get_customer_note(),
            'phone' => $order->get_billing_phone(),
            'countryIso' => $order->get_shipping_country()
        );

        $this->set_data_fields($data);
        $this->set_number($order);

        if (isset($this->settings[$order->get_status()])) {
            $this->set_data_field('status', $this->settings[$order->get_status()]);
        }

        return $this;
    }

    /**
     * @param WC_Order $order
     */
    protected function set_payment_data($order)
    {
        if ($order->get_payment_method() && isset($this->settings[$order->get_payment_method()])) {
            $this->set_data_field('paymentType', $this->settings[$order->get_payment_method()]);
        }

        if ($order->is_paid()) {
            $this->set_data_field('paymentStatus', 'paid');
        }
    }

    /**
     * @param WC_Order $order
     */
    protected function set_number($order)
    {
        if ($this->is_new) {
            $this->set_data_field('number', $order->get_order_number());
        }

        if (isset($this->settings['update_number'])
            && $this->settings['update_number'] == WC_Retailcrm_Base::YES
        ) {
            if (!$this->is_new) {
                $this->set_data_field('number', $order->get_order_number());
            }
        } elseif (!$this->is_new) {
            unset($this->data['number']);
        }
    }

    public function reset_data()
    {
        $this->data = array(
            'externalId' => '',
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
        );
    }
}
