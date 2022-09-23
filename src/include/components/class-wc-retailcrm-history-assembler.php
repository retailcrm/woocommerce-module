<?php

/**
 * PHP version 7.0
 *
 * Class WC_Retailcrm_History_Assembler - Assembles history records into list which closely resembles
 * orders & customers list output from API.
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */
class WC_Retailcrm_History_Assembler
{
    /**
     * Assembles orders list from history data
     *
     * @param array $orderHistory
     *
     * @return array
     */
    public static function assemblyOrder($orderHistory)
    {
        $fields = self::getMappingValues();
        $orders = [];
        $orderHistory = self::filterHistory($orderHistory, 'order');

        foreach ($orderHistory as $change) {
            $change['order'] = self::removeEmpty($change['order']);

            if (isset($change['order']['items']) && $change['order']['items']) {
                $items = [];

                foreach ($change['order']['items'] as $item) {
                    if (isset($change['created'])) {
                        $item['create'] = 1;
                    }

                    $items[$item['id']] = $item;
                }
                $change['order']['items'] = $items;
            }

            if (isset($change['order']['contragent']['contragentType']) && $change['order']['contragent']['contragentType']) {
                $change['order']['contragentType'] = $change['order']['contragent']['contragentType'];

                unset($change['order']['contragent']);
            }

            $orderMainInfo = WC_Retailcrm_Plugin::clearArray(
                [
                    'id'         => $change['order']['id'] ?? '',
                    'externalId' => $change['order']['externalId'] ?? '',
                    'managerId'  => $change['order']['managerId'] ?? '',
                    'site'       => $change['order']['site'] ?? '',
                ]
            );

            if (!empty($orders) && isset($orders[$change['order']['id']])) {
                $orders[$change['order']['id']] = array_merge($orders[$change['order']['id']], $orderMainInfo);
            } else {
                $orders[$change['order']['id']] = !empty($change['created']) ? $change['order'] : $orderMainInfo;
            }

            if ($change['field'] === 'status') {
                $orders[$change['order']['id']]['status'] = $change['order']['status'];
            }

            if (isset($change['item']) && $change['item']) {
                if (isset($orders[$change['order']['id']]['items'][$change['item']['id']])) {
                    $orders[$change['order']['id']]['items'][$change['item']['id']] = array_merge($orders[$change['order']['id']]['items'][$change['item']['id']], $change['item']);
                } else {
                    $orders[$change['order']['id']]['items'][$change['item']['id']] = $change['item'];
                }

                if ($change['oldValue'] === null && $change['field'] == 'order_product') {
                    $orders[$change['order']['id']]['items'][$change['item']['id']]['create'] = true;
                }

                if ($change['newValue'] === null && $change['field'] == 'order_product') {
                    $orders[$change['order']['id']]['items'][$change['item']['id']]['delete'] = true;
                }

                if (isset($fields['item'][$change['field']]) && $fields['item'][$change['field']]) {
                    $orders[$change['order']['id']]['items'][$change['item']['id']][$fields['item'][$change['field']]] = $change['newValue'];
                }
            }

            if (isset($change['payment']) && $change['field'] == 'payments') {
                if ($change['newValue'] !== null) {
                    $orders[$change['order']['id']]['payments'][] = self::newValue($change['payment']);
                }
            }

            if ($change['field'] == 'payments.status' && $change['newValue'] !== null) {
                $orders[$change['order']['id']]['payments']['id']['status'] = self::newValue($change['newValue']);
            } else {
                if (isset($fields['delivery'][$change['field']]) && $fields['delivery'][$change['field']] == 'service') {
                    $orders[$change['order']['id']]['delivery']['service']['code'] = self::newValue($change['newValue']);
                } elseif (isset($fields['delivery'][$change['field']]) && $fields['delivery'][$change['field']]) {
                    $orders[$change['order']['id']]['delivery'][$fields['delivery'][$change['field']]] = self::newValue($change['newValue']);
                } elseif (isset($fields['orderAddress'][$change['field']]) && $fields['orderAddress'][$change['field']]) {
                    $orders[$change['order']['id']]['delivery']['address'][$fields['orderAddress'][$change['field']]] = $change['newValue'];
                } elseif (isset($fields['integrationDelivery'][$change['field']]) && $fields['integrationDelivery'][$change['field']]) {
                    $orders[$change['order']['id']]['delivery']['service'][$fields['integrationDelivery'][$change['field']]] = self::newValue($change['newValue']);
                } elseif (isset($fields['customerContragent'][$change['field']]) && $fields['customerContragent'][$change['field']]) {
                    $orders[$change['order']['id']][$fields['customerContragent'][$change['field']]] = self::newValue($change['newValue']);
                } elseif (strripos($change['field'], 'custom_') !== false) {
                    $orders[$change['order']['id']]['customFields'][str_replace('custom_', '', $change['field'])] = self::newValue($change['newValue']);
                } elseif (isset($fields['order'][$change['field']]) && $fields['order'][$change['field']]) {
                    $orders[$change['order']['id']][$fields['order'][$change['field']]] = self::newValue($change['newValue']);
                }

                if (isset($change['created'])) {
                    $orders[$change['order']['id']]['create'] = 1;
                }

                if (isset($change['deleted'])) {
                    $orders[$change['order']['id']]['deleted'] = 1;
                }
            }
        }

        return $orders;
    }

    /**
     * Assembles customers list from history changes
     *
     * @param array $customerHistory
     *
     * @return array
     */
    public static function assemblyCustomer($customerHistory)
    {
        $customers = array();
        $fields = self::getMappingValues(array('customer'));
        $fieldsAddress = self::getMappingValues(array('customerAddress'));
        $customerHistory = self::filterHistory($customerHistory, 'customer');

        foreach ($customerHistory as $change) {
            $change['customer'] = self::removeEmpty($change['customer']);

            if (
                isset($change['deleted'])
                && $change['deleted']
                && isset($customers[$change['customer']['id']])
            ) {
                $customers[$change['customer']['id']]['deleted'] = true;
                continue;
            }

            if ($change['field'] == 'id') {
                $customers[$change['customer']['id']] = $change['customer'];
            }

            if (isset($customers[$change['customer']['id']])) {
                $customers[$change['customer']['id']] = array_merge($customers[$change['customer']['id']], $change['customer']);
            } else {
                $customers[$change['customer']['id']] = $change['customer'];
            }

            if (
                isset($fields['customer'][$change['field']])
                && $fields['customer'][$change['field']]
            ) {
                $customers[
                $change['customer']['id']
                ][
                $fields['customer'][$change['field']]
                ] = self::newValue($change['newValue']);
            }

            if (
                isset($fieldsAddress['customerAddress'][$change['field']])
                && $fieldsAddress['customerAddress'][$change['field']]
            ) {
                if (!isset($customers[$change['customer']['id']]['address'])) {
                    $customers[$change['customer']['id']]['address'] = array();
                }

                $customers[
                $change['customer']['id']
                ][
                'address'
                ][
                $fieldsAddress['customerAddress'][$change['field']]
                ] = self::newValue($change['newValue']);
            }

            if (strripos($change['field'], 'custom_') !== false) {
                $customers[$change['customer']['id']]['customFields'][str_replace( 'custom_', '', $change['field'])] = self::newValue($change['newValue']);
            }

            // email_marketing_unsubscribed_at old value will be null and new value will be datetime in
            // `Y-m-d H:i:s` format if customer was marked as unsubscribed in retailCRM
            if (isset($change['customer']['id']) && $change['field'] == 'email_marketing_unsubscribed_at') {
                if ($change['oldValue'] == null && is_string(self::newValue($change['newValue']))) {
                    $customers[$change['customer']['id']]['subscribed'] = false;
                } elseif (is_string($change['oldValue']) && self::newValue($change['newValue']) == null) {
                    $customers[$change['customer']['id']]['subscribed'] = true;
                }
            }
        }

        return $customers;
    }

    /**
     * Assembles corporate customers list from changes
     *
     * @param array $customerHistory
     *
     * @return array
     */
    public static function assemblyCorporateCustomer($customerHistory)
    {
        $fields = self::getMappingValues(array('customerCorporate', 'customerAddress'));
        $customersCorporate = array();

        foreach ($customerHistory as $change) {
            $change['customer'] = self::removeEmpty($change['customer']);

            if (
                isset($change['deleted'])
                && $change['deleted']
                && isset($customersCorporate[$change['customer']['id']])
            ) {
                $customersCorporate[$change['customer']['id']]['deleted'] = true;
                continue;
            }

            if (isset($customersCorporate[$change['customer']['id']])) {
                if (
                    isset($customersCorporate[$change['customer']['id']]['deleted'])
                    && $customersCorporate[$change['customer']['id']]['deleted']
                ) {
                    continue;
                }

                $customersCorporate[$change['customer']['id']] = array_merge(
                    $customersCorporate[$change['customer']['id']],
                    $change['customer']
                );
            } else {
                $customersCorporate[$change['customer']['id']] = $change['customer'];
            }

            if (
                isset($fields['customerCorporate'][$change['field']])
                && $fields['customerCorporate'][$change['field']]
            ) {
                $customersCorporate[
                $change['customer']['id']
                ][
                $fields['customerCorporate'][$change['field']]
                ] = self::newValue($change['newValue']);
            }

            if (isset($fields['customerAddress'][$change['field']]) && $fields['customerAddress'][$change['field']]) {
                if (empty($customersCorporate[$change['customer']['id']]['address'])) {
                    $customersCorporate[$change['customer']['id']]['address'] = array();
                }

                $customersCorporate[
                $change['customer']['id']
                ][
                'address'
                ][
                $fields['customerAddress'][$change['field']]
                ] = self::newValue($change['newValue']);
            }

            if ($change['field'] == 'address') {
                $customersCorporate[
                $change['customer']['id']
                ]['address'] = array_merge($change['address'], self::newValue($change['newValue']));
            }
        }

        foreach ($customersCorporate as $id => &$customer) {
            if (empty($customer['id']) && !empty($id)) {
                $customer['id'] = $id;
                $customer['deleted'] = true;
            }
        }

        return $customersCorporate;
    }

    /**
     * Returns mapping data for retailCRM entities. Used to assembly entities from history.
     *
     * @param array $groupFilter
     *
     * @return array
     */
    private static function getMappingValues($groupFilter = array())
    {
        $fields = array();
        $mappingFile = realpath(WC_Integration_Retailcrm::checkCustomFile('config/objects.xml'));

        if (file_exists($mappingFile)) {
            $objects = simplexml_load_file($mappingFile);

            foreach ($objects->fields->field as $object) {
                if (empty($groupFilter) || in_array($object["group"], $groupFilter)) {
                    $fields[(string)$object["group"]][(string)$object["id"]] = (string)$object;
                }
            }
        }

        return $fields;
    }

    /**
     * Value accessor
     *
     * @param array $value
     *
     * @return mixed
     */
    private static function newValue($value)
    {
        if (isset($value['code'])) {
            return $value['code'];
        } else {
            return $value;
        }
    }

    /**
     * Returns array without values which are considered empty
     *
     * @param array|\ArrayAccess $inputArray
     *
     * @return array
     */
    private static function removeEmpty($inputArray)
    {
        $outputArray = array();

        if (!empty($inputArray)) {
            foreach ($inputArray as $key => $element) {
                if (!empty($element) || $element === 0 || $element === '0') {
                    if (is_array($element)) {
                        $element = self::removeEmpty($element);
                    }

                    $outputArray[$key] = $element;
                }
            }
        }

        return $outputArray;
    }

    /**
     * Filters out history by these terms:
     *  - Changes from current API key will be added only if CMS changes are more actual than history.
     *  - All other changes will be merged as usual.
     * It fixes these problems:
     *  - Changes from current API key are merged when it's not needed.
     *  - Changes from CRM can overwrite more actual changes from CMS due to ignoring current API key changes.
     *
     * @param array  $historyEntries Raw history from CRM
     * @param string $recordType     Entity field name, e.g. `customer` or `order`.
     *
     * @return array
     */
    private static function filterHistory($historyEntries, $recordType)
    {
        $history = array();
        $organizedHistory = array();
        $notOurChanges = array();

        foreach ($historyEntries as $entry) {
            if (!isset($entry[$recordType]['externalId'])) {
                if (
                    $entry['source'] == 'api'
                    && isset($change['apiKey']['current'])
                    && $entry['apiKey']['current'] == true
                    && $entry['field'] != 'externalId'
                ) {
                    continue;
                } else {
                    $history[] = $entry;
                }

                continue;
            }

            $externalId = $entry[$recordType]['externalId'];
            $field = $entry['field'];

            if (!isset($organizedHistory[$externalId])) {
                $organizedHistory[$externalId] = array();
            }

            if (!isset($notOurChanges[$externalId])) {
                $notOurChanges[$externalId] = array();
            }

            if (
                $entry['source'] == 'api'
                && isset($entry['apiKey']['current'])
                && $entry['apiKey']['current'] == true
            ) {
                if (isset($notOurChanges[$externalId][$field]) || $entry['field'] == 'externalId') {
                    $organizedHistory[$externalId][] = $entry;
                } else {
                    continue;
                }
            } else {
                $organizedHistory[$externalId][] = $entry;
                $notOurChanges[$externalId][$field] = true;
            }
        }

        unset($notOurChanges);

        foreach ($organizedHistory as $historyChunk) {
            $history = array_merge($history, $historyChunk);
        }

        return $history;
    }
}