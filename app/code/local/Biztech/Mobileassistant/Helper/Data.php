<?php

class Biztech_Mobileassistant_Helper_Data extends Mage_Core_Helper_Abstract {

    public function create($data) {
        $collections = Mage::getModel("mobileassistant/mobileassistant")->getCollection()
                ->addFieldToFilter('username', Array('eq' => $data['username']))
                ->addFieldToFilter('password', Array('eq' => $data['password']))
                ->addFieldToFilter('device_token', Array('eq' => $data['devicetoken']));
        $count = count($collections);


        if ($count == 0) {
            Mage::getModel("mobileassistant/mobileassistant")
                    ->setUsername($data['username'])
                    ->setPassword($data['password'])
                    ->setDeviceToken($data['devicetoken'])
                    ->setDeviceType($data['device_type'])
                    ->setNotificationFlag($data['notification_flag'])
                    ->save();
        }
        if ($count == 1) {
            foreach ($collections as $user) {
                $user_id = $user->getUserId();
                $flag = $user->getNotificationFlag();
            }
            if ($flag != $data['notification_flag'] || $data['is_logout'] != 1) {
                try {
                    $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
                    $connection->beginTransaction();
                    $fields = array();
                    $fields['notification_flag'] = $data['notification_flag'];
                    $fields['is_logout'] = $data['is_logout'];
                    $where = $connection->quoteInto('user_id =?', $user_id);
                    $prefix = Mage::getConfig()->getTablePrefix();
                    $connection->update($prefix . 'mobileassistant', $fields, $where);
                    $connection->commit();
                } catch (Exception $e) {
                    return $e->getMessage();
                }
            }
        }

        $successArr[] = array('success_msg' => 'Login sucessfully', 'session_id' => $data['session_id']);

        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $storeArr[] = array('id' => $store->getId(),
                        'name' => $store->getName()
                    );
                }
            }
        }
        $isPos = 0;
        $result = array('success' => $successArr, 'stores' => $storeArr, 'is_pos' => $isPos, 'is_Mobileassistantpro' => 0);
        return $result;
    }

    public function getPrice($price, $storeId, $order_currency) {
        $currencyCode = $order_currency;
        if ($order_currency == null) {
            $store = Mage::getModel('core/store')->load($storeId);
            $price = $store->roundPrice($store->convertPrice($price));
            $currencyCode = Mage::app()->getStore($storeId)->getCurrentCurrencyCode();
        }
        $price = strip_tags($this->getPriceFormat($price));
        return $price;
    }

    public function getPriceFormat($price) {
        $price = sprintf("%01.2f", $price);
        return $price;
    }

    public function getActualDate($updated_date) {
        $date = Mage::app()->getLocale()->date(strtotime($updated_date));
        $timestamp = $date->get(Zend_Date::TIMESTAMP) - $date->get(Zend_Date::TIMEZONE_SECS);
        $updated_date = date("Y-m-d H:i:s", $timestamp);
        return $updated_date;
    }

    public function getActualOrderDate($updated_date) {
        $date = Mage::app()->getLocale()->date(strtotime($updated_date));
        $timestamp = $date->get(Zend_Date::TIMESTAMP) + $date->get(Zend_Date::TIMEZONE_SECS);
        $updated_date = date("Y-m-d H:i:s", $timestamp);
        return $updated_date;
    }

    public function isEnable() {
        return Mage::getStoreConfig('mobileassistant/mobileassistant_general/enabled');
    }

    public function pushNotification($notification_type, $entity_id, $params = NULL) {
        $google_api_key = 'AIzaSyAZPkT165oPcjfhUmgJnt5Lcs2OInBFJmE';
        $passphrase = 'push2magento';
        $collections = Mage::getModel("mobileassistant/mobileassistant")->getCollection()->addFieldToFilter('notification_flag', Array('eq' => 1))->addFieldToFilter('is_logout', Array('eq' => 0));

        if ($notification_type == 'customer') {
            $message = Mage::getStoreConfig('mobileassistant/mobileassistant_general/customer_register_notification_msg');
            if ($message == null) {
                $message = Mage::helper('mobileassistant')->__('A New customer has been registered on the Store.');
            }
        } else if ($notification_type == 'order') {

            $order = Mage::getModel('sales/order')->load($entity_id);
            $msgString = Mage::getStoreConfig('mobileassistant/mobileassistant_general/notification_msg');
            if ($msgString == null) {
                $msgString = Mage::helper('mobileassistant')->__('A New order has been received on the Store.');
            }
            $message = $msgString . "\nOrder Id: " . $order->getIncrementId() . "\nGrand Total: " . $this->getPrice($order->getGrandTotal(), $order->getStoreId(), $order->getOrderCurrencyCode());
        } else if ($notification_type == 'product') {
            $msgString = Mage::getStoreConfig('mobileassistant/mobileassistant_general/product_inventory_notification_msg');
            if ($msgString == null) {
                $msgString = Mage::helper('mobileassistant')->__('Product Stock Alert');
            }
            $message = $msgString . "\nName: " . $params['name'] . "\nCurrent Qty: " . $params['qty'];
        } else if ($notification_type == 'review') {
            $message = Mage::getStoreConfig('mobileassistant/mobileassistant_general/product_review_notification_msg');
            if ($message == null) {
                $message = Mage::helper('mobileassistant')->__('A New Review has been added on the store');
            }
        }

        $apnsCert = Mage::getBaseDir('lib') . DS . "mobileassistant" . DS . "MADist.pem";
        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', $apnsCert);
        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
        $flags = STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT;
        $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, $flags, $ctx);

        foreach ($collections as $collection) {
            $deviceType = $collection->getDeviceType();

            if ($deviceType == 'ios') {
                if ($fp) {

                    $deviceToken = $collection->getDeviceToken();
                    $body['aps'] = array(
                        'alert' => $message,
                        'sound' => 'default',
                        'entity_id' => $entity_id,
                        'type' => $notification_type
                    );

                    $payload = json_encode($body);
                    $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
                    $result = fwrite($fp, $msg, strlen($msg));
                }
            } elseif ($deviceType == 'android') {

                $deviceToken = $collection->getDeviceToken();
                $registrationIds = array($deviceToken);
                $msg_a = array(
                    'message' => $message,
                    'entity_id' => $entity_id,
                    'type' => $notification_type
                );

                $fields = array(
                    'registration_ids' => $registrationIds,
                    'data' => $msg_a
                );

                $headers = array(
                    'Authorization: key=' . $google_api_key,
                    'Content-Type: application/json'
                );

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://android.googleapis.com/gcm/send');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
                $result = curl_exec($ch);
                curl_close($ch);
            }
        }
        fclose($fp);
        return true;
    }

    public function getDataInfo() {
        $data = Mage::getStoreConfig('mobileassistant/activation/data');
        return Zend_Json::decode(base64_decode(Mage::helper('core')->decrypt($data)));
    }

    public function getFormatUrl($url) {
        $input = trim($url, '/');
        if (!preg_match('#^http(s)?://#', $input)) {
            $input = 'http://' . $input;
        }
        $urlParts = parse_url($input);
        $domain = preg_replace('/^www\./', '', $urlParts['host'] . $urlParts['path']);
        return $domain;
    }

    public function getAllStoreDomains() {
        $domains = array();
        foreach (Mage::app()->getWebsites() as $website) {
            $url = $website->getConfig('web/unsecure/base_url');
            if ($domain = trim(preg_replace('/^.*?\/\/(.*)?\//', '$1', $url))) {
                $domains[] = $domain;
            }
            $url = $website->getConfig('web/secure/base_url');
            if ($domain = trim(preg_replace('/^.*?\/\/(.*)?\//', '$1', $url))) {
                $domains[] = $domain;
            }
        }
        return array_unique($domains);
    }

    public function getAllWebsites() {
        if (!Mage::getStoreConfig('mobileassistant/activation/installed')) {
            return array();
        }
        $data = Mage::getStoreConfig('mobileassistant/activation/data');
        $web = Mage::getStoreConfig('mobileassistant/activation/websites');
        $websites = explode(',', str_replace($data, '', Mage::helper('core')->decrypt($web)));
        $websites = array_diff($websites, array(""));
        return $websites;
    }

}
