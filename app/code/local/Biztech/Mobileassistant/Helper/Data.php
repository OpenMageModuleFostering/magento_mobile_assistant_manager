<?php

    class Biztech_Mobileassistant_Helper_Data extends Mage_Core_Helper_Abstract
    {
        public function getPrice($price,$storeId,$order_currency)
        {    
            $currencyCode = $order_currency;
            if($order_currency == null)
            {
                $store= Mage::getModel('core/store')->load($storeId);
                $price = $store->roundPrice($store->convertPrice($price));
                $currencyCode = Mage::app()->getStore($storeId)->getCurrentCurrencyCode();
            }
            $price = strip_tags(Mage::app()->getLocale()->currency($currencyCode)->toCurrency($this->getPriceFormat($price)));
            return $price;
        } 

        public function getPriceFormat($price)
        {
            $price = sprintf("%01.2f", $price);
            return $price;
        }

        public function getActualDate($updated_date)
        {
            $date          = Mage::app()->getLocale()->date(strtotime($updated_date));
            $timestamp     = $date->get(Zend_Date::TIMESTAMP) - $date->get(Zend_Date::TIMEZONE_SECS);
            $updated_date  = date("Y-m-d H:i:s", $timestamp);
            return $updated_date;

        }

        public function getActualOrderDate($updated_date)
        {
            $date          = Mage::app()->getLocale()->date(strtotime($updated_date));
            $timestamp     = $date->get(Zend_Date::TIMESTAMP) + $date->get(Zend_Date::TIMEZONE_SECS);
            $updated_date  = date("Y-m-d H:i:s", $timestamp);
            return $updated_date;

        }

        public function isEnable()
        {
            return Mage::getStoreConfig('mobileassistant/mobileassistant_general/enabled');
        }

        public function pushNotification($notification_type,$entity_id,$params=NULL){

            $google_api_key = 'AIzaSyAZPkT165oPcjfhUmgJnt5Lcs2OInBFJmE';
            $passphrase  = 'push2magento';
            $collections = Mage::getModel("mobileassistant/mobileassistant")->getCollection()->addFieldToFilter('notification_flag',Array('eq'=>1))->addFieldToFilter('is_logout',Array('eq'=>0));

            if ($notification_type=='customer'){
                $notification_data = array();
                $message     = Mage::getStoreConfig('mobileassistant/mobileassistant_general/customer_register_notification_msg');
                if($message == null){
                    $message     = Mage::helper('mobileassistant')->__('A New customer has been registered on the Store.');
                }
            }else if($notification_type == 'order'){

                $order    = Mage::getModel('sales/order')->load($entity_id);
                $msgString =     Mage::getStoreConfig('mobileassistant/mobileassistant_general/notification_msg');
                if($msgString == null){
                    $msgString = Mage::helper('mobileassistant')->__('A New order has been received on the Store.');
                }
                $message     =$msgString."\nOrder Id: ".$order->getIncrementId()."\nGrand Total: ".$this->getPrice($order->getGrandTotal(),$order->getStoreId(),$order->getOrderCurrencyCode());

            }else if($notification_type == 'product'){
                $msgString =   Mage::getStoreConfig('mobileassistant/mobileassistant_general/product_inventory_notification_msg');
                if($msgString == null){
                    $msgString = Mage::helper('mobileassistant')->__('Product Stock Alert');
                }
                $message     = $msgString."\nName: ".$params['name']."\nCurrent Qty: ".$params['qty'];
            } 

            $apnsCert = Mage::getBaseDir('lib'). DS. "mobileassistant".DS."pushcert.pem";
            $ctx      = stream_context_create();
            stream_context_set_option($ctx, 'ssl', 'local_cert', $apnsCert);
            stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);      
            $flags = STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT;
            $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err,$errstr, 60, $flags, $ctx);

            foreach($collections as $collection){
                $deviceType = $collection->getDeviceType();

                if($deviceType == 'ios'){
                    if ($fp){

                        $deviceToken = $collection->getDeviceToken();
                        $body['aps'] = array(
                            'alert'     => $message,
                            'sound'     => 'default',
                            'entity_id' => $entity_id,
                            'type'      => $notification_type
                        );

                        $payload = json_encode($body);
                        $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
                        $result = fwrite($fp, $msg, strlen($msg));

                    }

                }elseif($deviceType == 'android')
                {

                    $deviceToken = $collection->getDeviceToken();
                    $registrationIds = array($deviceToken);
                    $msg_a = array(
                        'message'   => $message,
                        'entity_id' => $entity_id,
                        'type'      => $notification_type
                    );

                    $fields = array(
                        'registration_ids' => $registrationIds,
                        'data'    => $msg_a
                    );

                    $headers = array(
                        'Authorization: key=' . $google_api_key,
                        'Content-Type: application/json'
                    );

                    $ch = curl_init();
                    curl_setopt( $ch,CURLOPT_URL, 'https://android.googleapis.com/gcm/send' );
                    curl_setopt( $ch,CURLOPT_POST, true );
                    curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
                    curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
                    curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
                    curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
                    $result = curl_exec($ch );
                    curl_close( $ch );
                }
            }
            fclose($fp);
            return true;
        }
}