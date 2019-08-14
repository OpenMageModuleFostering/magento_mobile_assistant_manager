<?php
    class Biztech_Mobileassistant_Model_Observer
    {
        private static $_handleCustomerFirstOrderCounter = 1;
        public function sales_order_save_after(Varien_Event_Observer $observer)
        {  
            if (Mage::app()->getRequest()->getControllerName()=='onepage'){
                if(Mage::getStoreConfig('mobileassistant/mobileassistant_general/enabled')){
                    if (self::$_handleCustomerFirstOrderCounter > 1) {
                        return $this;
                    }
                    self::$_handleCustomerFirstOrderCounter++;
                    $collections = Mage::getModel("mobileassistant/mobileassistant")->getCollection()->addFieldToFilter('notification_flag',Array('eq'=>1));
                    $passphrase  = 'magento123';
                    $message     = Mage::getStoreConfig('mobileassistant/mobileassistant_general/notification_msg');
                    if($message == null){
                        $message     = Mage::helper('mobileassistant')->__('A New order has been received on the Store.');
                    }
                    $apnsCert = Mage::getBaseDir('lib'). DS. "mobileassistant/ck.pem";
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
                                'sound' => 'default'
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
        }
    }
