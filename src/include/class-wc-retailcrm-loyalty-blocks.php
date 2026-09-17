<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WC_Retailcrm_Loyalty_Blocks')) :
    /**
     * PHP version 7.1
     *
     * Class WC_Retailcrm_Loyalty_Blocks - Loyalty program data for the block-based cart and checkout (Store API).
     *
     * @category Integration
     * @author   RetailCRM <integration@retailcrm.ru>
     * @license  http://retailcrm.ru Proprietary
     * @link     http://retailcrm.ru
     * @see      http://help.retailcrm.ru
     */
    class WC_Retailcrm_Loyalty_Blocks
    {
        const EXTENSION_NAMESPACE = 'retailcrm-loyalty';

        const CART_ENDPOINT = 'cart';

        const CACHE_SESSION_KEY = 'retailcrm_loyalty_blocks';

        const SCRIPT_HANDLE = 'retailcrm-loyalty-blocks';

        /** Lifetime of the cached data in seconds: the bonus balance may change in CRM without cart changes. */
        const CACHE_TTL = 300;

        /** A missing account can't be told apart from an unavailable CRM, so this result is cached briefly. */
        const INACTIVE_CACHE_TTL = 60;

        /** @var WC_Retailcrm_Loyalty */
        private $loyalty;

        public function __construct(WC_Retailcrm_Loyalty $loyalty)
        {
            $this->loyalty = $loyalty;
        }

        public function init()
        {
            // Integrations are created on 'init', by that time 'woocommerce_blocks_loaded' has usually already fired
            if (did_action('woocommerce_blocks_loaded')) {
                $this->register();
            } else {
                add_action('woocommerce_blocks_loaded', [$this, 'register']);
            }

            // Fired only when the block is rendered, so it also works for blocks placed in block theme templates
            add_action('woocommerce_blocks_enqueue_cart_block_scripts_after', [$this, 'enqueue_assets']);
            add_action('woocommerce_blocks_enqueue_checkout_block_scripts_after', [$this, 'enqueue_assets']);
        }

        public function enqueue_assets()
        {
            if (wp_script_is(self::SCRIPT_HANDLE)) {
                return;
            }

            $scriptPath = plugin_dir_path(__FILE__) . '../assets/js/' . self::SCRIPT_HANDLE . '.js';

            wp_enqueue_script(
                self::SCRIPT_HANDLE,
                plugins_url() . WC_Retailcrm_Base::ASSETS_DIR . '/js/' . self::SCRIPT_HANDLE . '.js',
                ['wc-blocks-checkout', 'wc-blocks-data-store', 'wp-data', 'wp-element', 'wp-plugins'],
                filemtime($scriptPath),
                true
            );

            wp_localize_script(self::SCRIPT_HANDLE, 'RetailcrmLoyaltyBlocks', [
                'ajax_url' => admin_url('admin-ajax.php'),
                // The same nonce as in the classic cart, checked by create_loyalty_coupon
                'nonce' => wp_create_nonce('loyalty_coupon_nonce'),
                'translations' => [
                    'credit_bonuses' => __('Points will be awarded for this order', 'woo-retailcrm'),
                    'bonus_count' => __('Bonus count', 'woo-retailcrm'),
                    'use_bonuses' => __('Use bonuses', 'woo-retailcrm'),
                    'possible_write_off' => __('It is possible to write off', 'woo-retailcrm'),
                    'bonuses' => __('bonuses', 'woo-retailcrm'),
                    'incorrect_count' => __('Incorrect count of bonuses', 'woo-retailcrm'),
                    'using' => __('Using...', 'woo-retailcrm'),
                    'error_occurred' => __('Error occurred', 'woo-retailcrm'),
                ],
            ]);
        }

        public function register()
        {
            // Old WooCommerce without Store API extensions: the classic cart and checkout keep working
            if (!function_exists('woocommerce_store_api_register_endpoint_data')) {
                return;
            }

            // The wc/store/cart data store is shared by the cart and checkout blocks, so one endpoint covers both
            woocommerce_store_api_register_endpoint_data([
                'endpoint' => self::CART_ENDPOINT,
                'namespace' => self::EXTENSION_NAMESPACE,
                'data_callback' => [$this, 'get_data'],
                'schema_callback' => [$this, 'get_schema'],
                'schema_type' => ARRAY_A,
            ]);
        }

        /**
         * Called by Store API on every cart request, so it must not change the cart.
         */
        public function get_data(): array
        {
            $data = $this->getDefaultData();

            try {
                $customerId = $this->getCustomerId();
                $cart = WC()->cart;

                if (!$customerId || !$cart || $cart->is_empty()) {
                    return $data;
                }

                $cacheKey = md5(wp_json_encode([$customerId, $cart->get_cart_hash(), $cart->get_applied_coupons()]));
                $cachedData = $this->getCachedData($cacheKey);

                if ($cachedData !== null) {
                    return $cachedData;
                }

                if ($this->loyalty->hasActiveLoyaltyAccount($customerId)) {
                    $data['active'] = true;
                    $data['appliedBonus'] = $this->loyalty->getAppliedLoyaltyBonuses();

                    // Bonuses are charged by a single coupon, so there is nothing to charge while it is applied
                    if ($data['appliedBonus'] <= 0) {
                        $charge = $this->loyalty->getMaxChargeBonuses($customerId);

                        if ($charge !== null) {
                            $data['maxCharge'] = $charge['maxCharge'];
                            $data['chargeRate'] = $charge['chargeRate'];
                        }
                    }

                    $data['creditBonuses'] = $this->loyalty->calculateCreditBonuses($customerId);
                }

                // A failed calculation is not cached, so the data is requested again once CRM is available
                if (!$data['active']) {
                    $this->setCachedData($cacheKey, $data, self::INACTIVE_CACHE_TTL);
                } elseif (!$this->loyalty->hasCartCalculationError()) {
                    $this->setCachedData($cacheKey, $data, self::CACHE_TTL);
                }
            } catch (Throwable $exception) {
                WC_Retailcrm_Logger::exception(__METHOD__, $exception);

                return $this->getDefaultData();
            }

            return $data;
        }

        public function get_schema(): array
        {
            return [
                'active' => [
                    'description' => 'The customer has an active loyalty program account',
                    'type' => 'boolean',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
                'maxCharge' => [
                    'description' => 'Bonuses available for charge',
                    'type' => 'number',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
                'chargeRate' => [
                    'description' => 'Bonus exchange rate',
                    'type' => 'number',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
                'appliedBonus' => [
                    'description' => 'Bonuses charged by the applied loyalty coupon',
                    'type' => 'number',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
                'creditBonuses' => [
                    'description' => 'Bonuses that will be credited upon completion of the order',
                    'type' => 'number',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
            ];
        }

        protected function getCustomerId(): int
        {
            return WC()->customer ? (int) WC()->customer->get_id() : 0;
        }

        private function getDefaultData(): array
        {
            return [
                'active' => false,
                'maxCharge' => 0.0,
                'chargeRate' => 1.0,
                'appliedBonus' => 0.0,
                'creditBonuses' => 0.0,
            ];
        }

        private function getCachedData(string $key): ?array
        {
            $cache = WC()->session ? WC()->session->get(self::CACHE_SESSION_KEY) : null;

            if (!is_array($cache) || ($cache['key'] ?? null) !== $key || ($cache['expires'] ?? 0) < time()) {
                return null;
            }

            return $cache['data'];
        }

        private function setCachedData(string $key, array $data, int $ttl)
        {
            if (!WC()->session) {
                return;
            }

            WC()->session->set(self::CACHE_SESSION_KEY, [
                'key' => $key,
                'expires' => time() + $ttl,
                'data' => $data,
            ]);
        }
    }
endif;
