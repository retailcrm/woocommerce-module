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

/**
 * Class WC_Retailcrm_Order_Payment
 */
class WC_Retailcrm_Order_Payment extends WC_Retailcrm_Abstracts_Data
{
    /** @var string  */
    protected $filter_name = 'order_payment';

    /** @var array  */
    protected $data = array(
        'externalId' => '',
        'amount' => 0.00,
        'type' => '',
        'order' => array()
    );

    /** @var bool */
    public $is_new = true;

    /**
     * @var array
     */
    protected $settings = array();

    /**
     * WC_Retailcrm_Order_Item constructor.
     *
     * @param array $settings
     */
    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param WC_Order $order
     * @param mixed $externalId
     *
     * @return self
     */
    public function build($order, $externalId = false)
    {
        $data = array(
            'amount' => (double) $order->get_total()
        );

        if (!$this->is_new) {
            $data['externalId'] = $externalId;
        } else {
            $data['externalId'] = uniqid($order->get_id());
        }

        $data['order'] = array(
            'externalId' => $order->get_id()
        );

        if ($order->is_paid()) {
            $data['status'] = 'paid';
        }

        if ($order->get_date_paid()) {
            $data['paidAt'] = $order->get_date_paid()->date('Y-m-d H:i:s');
        }

        if ($this->is_new) {
            if (isset($this->settings[$order->get_payment_method()])) {
                $data['type'] = $this->settings[$order->get_payment_method()];
            }
        }

        $this->set_data_fields($data);

        return $this;
    }

    public function reset_data()
    {
        $this->data = array(
            'externalId' => '',
            'amount' => 0.00,
            'type' => '',
            'status' => '',
            'paidAt' => '',
            'order' => array()
        );
    }
}
