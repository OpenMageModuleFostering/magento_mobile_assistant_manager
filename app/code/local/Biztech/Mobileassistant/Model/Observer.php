<?php

class Biztech_Mobileassistant_Model_Observer {

    private static $_handleCustomerFirstOrderCounter = 1;
    private static $_handleCustomerFirstRegisterNotificationCounter = 1;

    public function checkKey($observer) {
        $key = Mage::getStoreConfig('mobileassistant/activation/key');
        Mage::helper('mobileassistant')->checkKey($key);
    }

    /* inventory status-starts */

    public function catalogInventorySave(Varien_Event_Observer $observer) {
        if (Mage::getStoreConfig('mobileassistant/mobileassistant_general/enabled')) {
            $event = $observer->getEvent();
            $_item = $event->getItem();
            $params = array();
            $params['product_id'] = $_item->getProductId();
            $params['name'] = Mage::getModel('catalog/product')->load($params['product_id'])->getName();
            $params['qty'] = $_item->getQty();
            $minQty = Mage::getStoreConfig('mobileassistant/mobileassistant_general/minimum_qty');

            $data = array();
            $data['status'] = Mage::getStoreConfig('mobileassistant/mobileassistant_general/notification');
            $statuses = explode(",", $data['status']);
            if (in_array('product_notification', $statuses)) {
                if ($params['qty'] <= $minQty) {
                    Mage::helper('mobileassistant')->pushNotification('product', $params['product_id'], $params);
                }
            }
        }
    }

    public function subtractQuoteInventory(Varien_Event_Observer $observer) {
        if (Mage::getStoreConfig('mobileassistant/mobileassistant_general/enabled')) {
            $quote = $observer->getEvent()->getQuote();
            foreach ($quote->getAllItems() as $item) {
                $params = array();
                $params['product_id'] = $item->getProductId();
                $params['name'] = $item->getName();
                $params['qty'] = $item->getProduct()->getStockItem()->getQty() - $item->getTotalQty();
                $minQty = Mage::getStoreConfig('mobileassistant/mobileassistant_general/minimum_qty');
                $data = array();
                $data['status'] = Mage::getStoreConfig('mobileassistant/mobileassistant_general/notification');
                $statuses = explode(",", $data['status']);
                if (in_array('product_notification', $statuses)) {
                    if (($params['qty']) <= $minQty) {
                        Mage::helper('mobileassistant')->pushNotification('product', $params['product_id'], $params);
                    }
                }
            }
        }
    }

    public function revertQuoteInventory(Varien_Event_Observer $observer) {
        if (Mage::getStoreConfig('mobileassistant/mobileassistant_general/enabled')) {
            $quote = $observer->getEvent()->getQuote();
            foreach ($quote->getAllItems() as $item) {
                $params = array();
                $params['product_id'] = $item->getProductId();
                $params['name'] = $item->getName();
                $params['qty'] = $item->getProduct()->getStockItem()->getQty() + $item->getTotalQty();
                $minQty = Mage::getStoreConfig('mobileassistant/mobileassistant_general/minimum_qty');
                $data = array();
                $data['status'] = Mage::getStoreConfig('mobileassistant/mobileassistant_general/notification');
                $statuses = explode(",", $data['status']);
                if (in_array('product_notification', $statuses)) {
                    if (($params['qty']) <= $minQty) {
                        Mage::helper('mobileassistant')->pushNotification('product', $params['product_id'], $params);
                    }
                }
            }
        }
    }

    /* inventory status- ends */

    public function sales_order_save_after(Varien_Event_Observer $observer) {
        if (Mage::getStoreConfig('mobileassistant/mobileassistant_general/enabled')) {

            $action = Mage::app()->getFrontController()->getAction();
            if ($action->getFullActionName() == 'checkout_onepage_saveOrder') {
                if (self::$_handleCustomerFirstOrderCounter > 1) {
                    return $this;
                }
                self::$_handleCustomerFirstOrderCounter++;

                $data = array();
                $data['status'] = Mage::getStoreConfig('mobileassistant/mobileassistant_general/notification');
                $statuses = explode(",", $data['status']);
                if (in_array('order_notification', $statuses)) {
                    $result = Mage::helper('mobileassistant')->pushNotification('order', $observer->getEvent()->getOrder()->getId());
                }

                $quoteId = $observer->getEvent()->getOrder()->getData('quote_id');
                $quote = Mage::getModel('sales/quote')->load($quoteId);
                $method = $quote->getCheckoutMethod(true);

                if ($method == 'register') {
                    Mage::dispatchEvent('customer_register_checkout', array(
                        'customer' => $observer->getEvent()->getOrder()->getCustomer()
                            )
                    );
                }
            }
        }
    }

    public function customerRegisterNotification(Varien_Event_Observer $observer) {
        if (Mage::getStoreConfig('mobileassistant/mobileassistant_general/enabled')) {
            $customer = $observer->getEvent()->getCustomer();
            if ($customer) {
                $customer_id = $customer->getId();
            }
            if ($customer_id) {
                $data = array();
                $data['status'] = Mage::getStoreConfig('mobileassistant/mobileassistant_general/notification');
                $statuses = explode(",", $data['status']);
                if (in_array('customer_notification', $statuses)) {
                    $result = Mage::helper('mobileassistant')->pushNotification('customer', $customer_id);
                }
            }
        }
    }

    public function customerRegisterNotificationCheckout(Varien_Event_Observer $observer) {
        $customer = $observer->getEvent()->getCustomer();
        if ($customer) {
            $customer_id = $customer->getId();
            $data = array();
            $data['status'] = Mage::getStoreConfig('mobileassistant/mobileassistant_general/notification');
            $statuses = explode(",", $data['status']);
            if (in_array('customer_notification', $statuses)) {
                $result = Mage::helper('mobileassistant')->pushNotification('customer', $customer_id);
            }
        }
    }

    public function reviewNotification(Varien_Event_Observer $observer) {
        $object = $observer->getEvent()->getObject();
        $statusId = $object->getStatusId();
        $action = Mage::app()->getFrontController()->getAction();
        if ($action->getFullActionName() == 'review_product_post') {
            if ($statusId) {
                $data = array();
                $data['status'] = Mage::getStoreConfig('mobileassistant/mobileassistant_general/notification');
                $statuses = explode(",", $data['status']);
                if (in_array('review_notification', $statuses)) {
                    $result = Mage::helper('mobileassistant')->pushNotification('review', $statusId);
                }
            }
        }
    }

}
