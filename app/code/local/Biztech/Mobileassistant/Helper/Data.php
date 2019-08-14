<?php

    class Biztech_Mobileassistant_Helper_Data extends Mage_Core_Helper_Abstract
    {

        public function getPriceFormat($price)
        {
            $price = sprintf("%01.2f", $price);
            return $price;
        }

        public function isEnable()
        {
            return Mage::getStoreConfig('mobileassistant/mobileassistant_general/enabled');
        }

        public function pushNotification($notification_type,$entity_id){
            $passphrase  = 'push2magento';
            $collections = Mage::getModel("mobileassistant/mobileassistant")->getCollection()->addFieldToFilter('notification_flag',Array('eq'=>1));

            if ($notification_type=='customer'){
                $message     = Mage::getStoreConfig('mobileassistant/mobileassistant_general/customer_register_notification_msg');
                if($message == null){
                    $message     = Mage::helper('mobileassistant')->__('A New customer has been registered on the Store.');
                }
            }else{
                $message     = Mage::getStoreConfig('mobileassistant/mobileassistant_general/notification_msg');
                if($message == null){
                    $message     = Mage::helper('mobileassistant')->__('A New order has been received on the Store.');
                }
            }

            $apnsCert = Mage::getBaseDir('lib'). DS. "mobileassistant".DS."magentoPushDst.pem";
            $ctx      = stream_context_create();
            stream_context_set_option($ctx, 'ssl', 'local_cert', $apnsCert);
            stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);      
            $flags = STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT;
            $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err,$errstr, 60, $flags, $ctx);
            if ($fp){
                foreach($collections as $collection){
                    $deviceToken = $collection->getDeviceToken();
                    $body['aps'] = array(
                        'alert' => $message,
                        'sound' => 'default',
                        'entity_id'=>$entity_id,
                        'type'      => $notification_type
                    );
                    $payload = json_encode($body);
                    $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
                    $result = fwrite($fp, $msg, strlen($msg));
                }
                fclose($fp);
            }
            return true;
        }
}