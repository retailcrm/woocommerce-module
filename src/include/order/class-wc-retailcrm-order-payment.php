<?php

/**
 * PHP version 7.0
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
        $this->resetData();

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
            if ($order->get_status() != 'completed' && $order->get_payment_method() == 'cod') {
                writeBaseLogs(
                    implode(
                        ' ',
                        [
                            'Payment for order: ' . $order->get_id(),
                            'Payment status cannot be changed as it is cash (or other payment method) on delivery.',
                            'The status will be changed when the order is in status completed.',
                        ]
                    )
                );
            } else {
                $paymentData['status'] = 'paid';

                if (isset($this->settings[$order->get_payment_method()])) {
                    $paymentData['type'] = $this->settings[$order->get_payment_method()];
                }

                $paidAt = $order->get_date_paid();

                if (!empty($paidAt)) {
                    $paymentData['paidAt'] = $paidAt->date('Y-m-d H:i:s');
                }
            }
        }

        if ($this->isNew) {
            if (isset($this->settings[$order->get_payment_method()])) {
                $paymentData['type'] = $this->settings[$order->get_payment_method()];
            } else {
                $paymentData = [];
            }
        }

        $paymentData = apply_filters(
            'retailcrm_process_payment',
            WC_Retailcrm_Plugin::clearArray($paymentData),
            $order
        );

        $this->setDataFields($paymentData);

        return $this;
    }

    /**
     * Returns false if payment doesn't have method
     *
     * @return array
     */
    public function getData()
    {
        $data = parent::getData();

        if (empty($data['type'])) {
            return [];
        }

        // Need to clear the array from empty values
        return array_filter($data);
    }

    public function resetData()
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
