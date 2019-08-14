<?php
    class Biztech_Mobileassistant_Model_Observer
    {
        private static $_handleCustomerFirstOrderCounter = 1;
        private static $_handleCustomerFirstRegisterNotificationCounter = 1;
        public function sales_order_save_after(Varien_Event_Observer $observer)
        {  
                if(Mage::getStoreConfig('mobileassistant/mobileassistant_general/enabled')){
                    if (self::$_handleCustomerFirstOrderCounter > 1) {
                        return $this;
                    }
                    self::$_handleCustomerFirstOrderCounter++;
                    $result = Mage::helper('mobileassistant')->pushNotification('order',$observer->getEvent()->getOrder()->getId());

                    $quoteId = $observer->getEvent()->getOrder()->getData('quote_id');
                    $quote = Mage::getModel('sales/quote')->load($quoteId);
                    $method = $quote->getCheckoutMethod(true);

                    if ($method=='register'){
                        Mage::dispatchEvent('customer_register_checkout',
                            array(
                                'customer' => $observer->getEvent()->getOrder()->getCustomer()
                            )
                        );
                    }
                }
        }

        public function customerRegisterNotification(Varien_Event_Observer $observer){
            if(Mage::getStoreConfig('mobileassistant/mobileassistant_general/enabled')){
                $customer               =   $observer->getEvent()->getCustomer();
                if ($customer){
                    $customer_id        =   $customer->getId();
                }    
                if ($customer_id){
                    $result = Mage::helper('mobileassistant')->pushNotification('customer',$customer_id);
                }
            }            
        }

        public function customerRegisterNotificationCheckout(Varien_Event_Observer $observer){
            $customer = $observer->getEvent()->getCustomer();
            if ($customer){
                $customer_id        =   $customer->getId();
                $result = Mage::helper('mobileassistant')->pushNotification('customer',$customer_id);
            }    
        }
    }
