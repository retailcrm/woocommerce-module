<?php
/**
 * PHP version 5.6
 *
 * Class WC_Retailcrm_Order_Payment - Build payments for CRM order.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_Order_Payment extends WC_Retailcrm_Abstracts_Data
{
    /** @var array  */
    protected $data = [
        'type'       => '',
        'order'      => [],
        'externalId' => '',
    ];

    /** @var bool */
    public $isNew = true;

    /**
     * @var array
     */
    protected $settings = [];

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
        $this->reset_data();
        $paymentData = [];

        if (!$this->isNew) {
            $paymentData['externalId'] = $externalId;
        } else {
            $paymentData['externalId'] = uniqid($order->get_id() . '-');
        }

        $paymentData['order'] = [
            'externalId' => $order->get_id()
        ];

        if ($order->is_paid()) {
            $paymentData['status'] = 'paid';

            if (isset($this->settings[$order->get_payment_method()])) {
                $paymentData['type'] = $this->settings[$order->get_payment_method()];
            }
        }

        $paidAt = $order->get_date_paid();

        if (!empty($paidAt)) {
            $paymentData['paidAt'] = $paidAt->date('Y-m-d H:i:s');
        }

        if ($this->isNew) {
            if (isset($this->settings[$order->get_payment_method()])) {
                $paymentData['type'] = $this->settings[$order->get_payment_method()];
            } else {
                $paymentData = [];
            }
        }

        $this->set_data_fields($paymentData);

        return $this;
    }

    /**
     * Returns false if payment doesn't have method
     *
     * @return array
     */
    public function get_data()
    {
        $data = parent::get_data();

        if (empty($data['type'])) {
            return [];
        }

        // Need to clear the array from empty values
        return array_filter($data);
    }

    public function reset_data()
    {
        $this->data = [
            'externalId' => '',
            'type' => '',
            'status' => '',
            'paidAt' => '',
            'order' => []
        ];
    }
}