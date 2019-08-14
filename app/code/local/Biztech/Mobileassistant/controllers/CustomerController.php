<?php

class Biztech_Mobileassistant_CustomerController extends Mage_Core_Controller_Front_Action {

    public function getCustomerListAction() {
        if (Mage::helper('mobileassistant')->isEnable()) {
            $post_data = Mage::app()->getRequest()->getParams();

            $sessionId = '';
            $limit = '';
            $offset = '';
            $new_customers = '';
            $is_refresh = '';
            $last_fetch_customer = '';
            $min_fetch_customer = '';
            if (isset($post_data['session'])) {
                $sessionId = $post_data['session'];
            }

            if (!$sessionId || $sessionId == NULL) {
                echo $this->__("The Login has expired. Please try log in again.");
                return false;
            }

            if (isset($post_data['limit'])) {
                $limit = $post_data['limit'];
            }
            if (isset($post_data['offset'])) {
                $offset = $post_data['offset'];
            }
            if (isset($post_data['last_fetch_customer'])) {
                $new_customers = $post_data['last_fetch_customer'];
            }
            if (isset($post_data['is_refresh'])) {
                $is_refresh = $post_data['is_refresh'];
            }
            $customers = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect('*')->setOrder('entity_id', 'desc');

            if ($offset != null) {
                $customers->addAttributeToFilter('entity_id', array('lt' => $offset));
            }

            if ($is_refresh == 1) {
                if (isset($post_data['last_fetch_customer'])) {
                    $last_fetch_customer = $post_data['last_fetch_customer'];
                }
                if (isset($post_data['min_fetch_customer'])) {
                    $min_fetch_customer = $post_data['min_fetch_customer'];
                }
                $last_updated = Mage::helper('mobileassistant')->getActualDate($post_data['last_updated']);
                $customers->getSelect()->where("(e.entity_id BETWEEN '" . $min_fetch_customer . "'AND '" . $last_fetch_customer  . "') OR e.entity_id >'" . $last_fetch_customer . "'");
            }

            $customers->getSelect()->limit($limit);
            foreach ($customers as $customer) {
                $billing_address = Mage::getModel('customer/address')->load($customer->getDefaultBilling());
                $shipping_address = Mage::getModel('customer/address')->load($customer->getDefaultShipping());

                $customer_list[] = array(
                    'entity_id' => $customer->getEntityId(),
                    'firstname' => $customer->getFirstname(),
                    'lastname' => $customer->getLastname(),
                    'email_id' => $customer->getEmail(),
                    'telephone' => $billing_address->getData('telephone'),
                    'billing_address_id' => $billing_address->getId(),
                    'shipping_address_id' => $shipping_address->getId()
                );
            }
            $updated_time = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));
            $customerListResultArr = array('customerlistdata' => $customer_list, 'updated_time' => $updated_time);
            $customerListResult = Mage::helper('core')->jsonEncode($customerListResultArr);
            return Mage::app()->getResponse()->setBody($customerListResult);
        } else {
            $isEnable = Mage::helper('core')->jsonEncode(array('enable' => false));
            return Mage::app()->getResponse()->setBody($isEnable);
        }
    }

    public function getCustomerDetailAction() {
        if (Mage::helper('mobileassistant')->isEnable()) {
            $post_data = Mage::app()->getRequest()->getParams();

            $sessionId = '';
            $customer_id = '';
            if (isset($post_data['session'])) {
                $sessionId = $post_data['session'];
            }

            if (!$sessionId || $sessionId == NULL) {
                echo $this->__("The Login has expired. Please try log in again.");
                return false;
            }
            if (isset($post_data['customer_id'])) {
                $customer_id = $post_data['customer_id'];
            }
            $customerData = Mage::getModel('customer/customer')->load($customer_id);


            $basic_detail = array(
                'entity_id' => $customerData->getEntityId(),
                'firstname' => $customerData->getFirstname(),
                'lastname' => $customerData->getLastname(),
                'email' => $customerData->getEmail(),
            );

            foreach ($customerData->getAddresses() as $address) {
                $billing_type = 0;
                $shipping_type = 0;
                $billing_country_name = null;

                if ($address->getCountryId()) {
                    $billing_country_name = Mage::getModel('directory/country')->loadByCode($address->getCountryId())->getName();
                }

                if ($address->getId() == $customerData->getDefaultBilling())
                    $billing_type = 1;

                if ($address->getId() == $customerData->getDefaultShipping())
                    $shipping_type = 1;

                $billing_address_detail[] = array(
                    'firstname' => $address->getFirstname(),
                    'lastname' => $address->getLastname(),
                    'street' => $address->getData('street'),
                    'city' => $address->getCity(),
                    'region_id' => $address->getRegionId() ? $address->getRegionId() : '',
                    'region' => $address->getRegion(),
                    'postcode' => $address->getPostcode(),
                    'country' => $billing_country_name,
                    'country_id' => $address->getCountryId(),
                    'telephone' => $address->getTelephone(),
                    'address_id' => $address->getId(),
                    'billing_type' => $billing_type,
                    'shipping_type' => $shipping_type
                );
            }

            $customer_detail = array(
                'basic_details' => $basic_detail,
                'address' => $billing_address_detail,
            );
            $order_detail = $this->_getCustomerOrderList($customer_id);

            $customerDetailResultArr = array('customerDetails' => $customer_detail, 'customerOrderDetail' => $order_detail);
            $customerDetailResult = Mage::helper('core')->jsonEncode($customerDetailResultArr);
            return Mage::app()->getResponse()->setBody($customerDetailResult);
        }else {
            $isEnable = Mage::helper('core')->jsonEncode(array('enable' => false));
            return Mage::app()->getResponse()->setBody($isEnable);
        }
    }

    protected function _getCustomerOrderList($customer_id) {
        $orderListData = array();
        $orderCollection = Mage::getResourceModel('sales/order_grid_collection')->addFieldToFilter('customer_id', Array('eq' => $customer_id))->setOrder('entity_id', 'desc');
        $limit = 5;
        $orderCollection->getSelect()->limit($limit);

        foreach ($orderCollection as $order) {

            $orderListData[] = array(
                'entity_id' => $order->getEntityId(),
                'increment_id' => $order->getIncrementId(),
                'store_id' => $order->getStoreId(),
                'customer_name' => $order->getBillingName(),
                'status' => $order->getStatus(),
                'order_date' => date('Y-m-d H:i:s', strtotime($order->getCreatedAt())),
                'grand_total' => Mage::helper('mobileassistant')->getPrice($order->getGrandTotal(), $order->getStoreId(), $order->getOrderCurrencyCode()),
                'toal_qty' => Mage::getModel('sales/order')->load($order->getEntityId())->getTotalQtyOrdered()
            );
        }
        return $orderListData;
    }

    public function getFilterCustomerListAction() {
        if (Mage::helper('mobileassistant')->isEnable()) {
            $post_data = Mage::app()->getRequest()->getParams();

            $sessionId = '';
            $search = '';
            if (isset($post_data['session'])) {
                $sessionId = $post_data['session'];
            }

            if (!$sessionId || $sessionId == NULL) {
                echo $this->__("The Login has expired. Please try log in again.");
                return false;
            }

            if (isset($post_data['search_content'])) {
                $search = $post_data['search_content'];
            }
            $customers = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect('*')->setOrder('entity_id', 'desc');

            if ($search != null) {
                $customers->addAttributeToFilter(array(
                    array(
                        'attribute' => 'firstname',
                        'like' => '%' . $search . '%'
                    ),
                    array(
                        'attribute' => 'lastname',
                        'like' => '%' . $search . '%'
                    ),
                    array(
                        'attribute' => 'email',
                        'like' => '%' . $search . '%'
                    )
                ));
            }

            foreach ($customers as $customer) {
                $billing_address = Mage::getModel('customer/address')->load($customer->getDefaultBilling());
                $shipping_address = Mage::getModel('customer/address')->load($customer->getDefaultShipping());

                $customer_list[] = array(
                    'entity_id' => $customer->getEntityId(),
                    'firstname' => $customer->getFirstname(),
                    'lastname' => $customer->getLastname(),
                    'email_id' => $customer->getEmail(),
                    'telephone' => $billing_address->getData('telephone'),
                    'billing_address_id' => $billing_address->getId(),
                    'shipping_address_id' => $shipping_address->getId(),
                );
            }
            $customerListResultArr = array('customerlistdata' => $customer_list);
            $customerListResult = Mage::helper('core')->jsonEncode($customerListResultArr);
            return Mage::app()->getResponse()->setBody($customerListResult);
        } else {
            $isEnable = Mage::helper('core')->jsonEncode(array('enable' => false));
            return Mage::app()->getResponse()->setBody($isEnable);
        }
    }

}
