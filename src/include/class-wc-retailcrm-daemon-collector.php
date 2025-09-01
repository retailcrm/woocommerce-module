<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_Daemon_Collector - Integration with Daemon Collector.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_Daemon_Collector {
    /** @var self $instance */
    private static $instance;

    /** @var array $options */
    private $options;

    /** @var string $code */
    private $code = '';

    /**
     * @param array $options
     *
     * @return WC_Retailcrm_Daemon_Collector
     */
    public static function getInstance($options = array())
    {
        if (self::$instance === null) {
            self::$instance = new self($options);
        }

        return self::$instance;
    }

    /**
     * WC_Retailcrm_Daemon_Collector constructor.
     *
     * @param array $options
     */
    private function __construct($options = array())
    {
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function initialize_daemon_collector() {
        if (!$this->code) {
            $this->buildHeader()
                ->buildParams()
                ->buildFooter();
        }

        return $this->code;
    }

    /**
     * @return $this
     */
    private function buildHeader() {
        $header  = '<script type="text/javascript">';
        $header .= '(function(_,r,e,t,a,i,l){_["retailCRMObject"]=a;_[a]=_[a]||function(){(_[a].q=_[a].q||[]).push(arguments)};';
        $header .= '_[a].l=1*new Date();l=r.getElementsByTagName(e)[0];i=r.createElement(e);i.async=!0;';
        $header .= 'i.src=t;l.parentNode.insertBefore(i,l)})(window,document,"script","https://collector.retailcrm.pro/w.js","_rc");';

        $this->code .= $header;

        return $this;
    }

    /**
     * @return $this
     */
    private function buildParams() {
        $params = array();

        if (
            function_exists('WC')
            && WC()->customer !== null
            && WC()->customer->get_id() > 0
        ) {
            $params['customer']['externalId'] = WC()->customer->get_id();
        }

        $this->code .= apply_filters('retailcrm_daemon_collector', '') . sprintf(
            '\t_rc(\'create\', \'%1$s\', %2$s);\n',
            $this->options['daemon_collector_key'],
            json_encode((object) $params)
        );

        return $this;
    }

    /**
     * @return $this
     */
    private function buildFooter() {
        $footer = '_rc(\'send\', \'pageView\')';
        $footer .= '</script>';

        $this->code .= $footer;

        return $this;
    }
}
