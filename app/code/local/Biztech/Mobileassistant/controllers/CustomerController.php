<?php
    class Biztech_Mobileassistant_CustomerController extends Mage_Core_Controller_Front_Action
    {
        public function getCustomerListAction()
        {
            if(Mage::helper('mobileassistant')->isEnable()){
                $post_data = Mage::app()->getRequest()->getParams();
                $sessionId = $post_data['session'];
                if (!Mage::getSingleton('api/session')->isLoggedIn($sessionId)) {
                    echo $this->__("The Login has expired. Please try log in again.");
                    return false;
                }
                $limit          =   $post_data['limit'];
                $offset         =   $post_data['offset'];
                $new_customers  =   $post_data['last_fetch_customer'];
                $is_refresh     =   $post_data['is_refresh'];

                $customers      =   Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect('*')->setOrder('entity_id', 'desc');

                if($offset != null){
                    $customers->addAttributeToFilter('entity_id', array('lt' => $offset));
                }
                

                if($is_refresh == 1){
                    $last_fetch_customer        =   $post_data['last_fetch_customer'];
                    $min_fetch_customer         =   $post_data['min_fetch_customer'];
                    $last_updated               =   $post_data['last_updated'];

                    $customers->getSelect()->where("(entity_id BETWEEN '".$min_fetch_customer."'AND '".$last_fetch_customer ."' AND updated_at > '".$last_updated."') OR entity_id >'".$last_fetch_customer."'");
                }


                $customers->getSelect()->limit($limit);
                foreach($customers as $customer){
                    $website_name = Mage::app()->getWebsite($customer->getWebsiteId())->getName();
                    $customer_list[] = array(
                        'entity_id'     => $customer->getEntityId(),
                        'customer_name' => $customer->getFirstname()." ".$customer->getLastname(),
                        'email_id'      => $customer->getEmail(),
                        'website_name'  => $website_name
                    );
                }
                $updated_time       = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));
                $customerListResultArr = array('customerlistdata' => $customer_list,'updated_time' =>$updated_time);
                $customerListResult    = Mage::helper('core')->jsonEncode($customerListResultArr);
                return Mage::app()->getResponse()->setBody($customerListResult);
            }else{
                $isEnable    = Mage::helper('core')->jsonEncode(array('enable' => false));
                return Mage::app()->getResponse()->setBody($isEnable);
            }
        }

        public function getCustomerDetailAction()
        {
            if(Mage::helper('mobileassistant')->isEnable()){
                $post_data = Mage::app()->getRequest()->getParams();
                $sessionId = $post_data['session'];
                if (!Mage::getSingleton('api/session')->isLoggedIn($sessionId)) {
                    echo $this->__("The Login has expired. Please try log in again.");
                    return false;
                }
                $customer_id  = $post_data['customer_id'];
                $customerData = Mage::getModel('customer/customer')->load($customer_id);


                $basic_detail = array(
                    'entity_id' => $customerData->getEntityId(),
                    'name'      => $customerData->getFirstname()." ".$customerData->getLastname(),
                    'email'     => $customerData->getEmail(),
                );

                $billing_address  = Mage::getModel('customer/address')->load($customerData->getDefaultBilling());
                $shipping_address = Mage::getModel('customer/address')->load($customerData->getDefaultShipping());

                $billing_country_name  = null;
                $shipping_country_name = null;

                if($billing_address->getCountryId()){
                    $billing_country_name = Mage::getModel('directory/country')->loadByCode($billing_address->getCountryId())->getName();
                }
                if($shipping_address->getCountryId()){
                    $shipping_country_name = Mage::getModel('directory/country')->loadByCode($shipping_address->getCountryId())->getName();
                }

                $billing_address_detail = array(
                    'name'      => $billing_address->getFirstname()." ".$billing_address->getLastname(),
                    'street'    => $billing_address->getData('street'),
                    'city'      => $billing_address->getCity(),
                    'region'    => $billing_address->getRegion(),
                    'postcode'  => $billing_address->getPostcode(),
                    'country'   => $billing_country_name,
                    'telephone' => $billing_address->getTelephone()
                );

                $shipping_address_detail = array(
                    'name'      => $shipping_address->getFirstname()." ".$shipping_address->getLastname(),
                    'street'    => $shipping_address->getData('street'),
                    'city'      => $shipping_address->getCity(),
                    'region'    => $shipping_address->getRegion(),
                    'postcode'  => $shipping_address->getPostcode(),
                    'country'   => $shipping_country_name,
                    'telephone' => $shipping_address->getTelephone()
                );

                $customer_detail = array(
                    'basic_details'    => $basic_detail,
                    'billing_address'  => $billing_address_detail,
                    'shipping_address' => $shipping_address_detail
                );
                $order_detail = $this->_getCustomerOrderList($customer_id);

                $customerDetailResultArr = array('customerDetails' => $customer_detail,'customerOrderDetail' =>$order_detail);
                $customerDetailResult    = Mage::helper('core')->jsonEncode($customerDetailResultArr);
                return Mage::app()->getResponse()->setBody($customerDetailResult);
            }else{
                $isEnable    = Mage::helper('core')->jsonEncode(array('enable' => false));
                return Mage::app()->getResponse()->setBody($isEnable);
            }
        }

        protected function _getCustomerOrderList($customer_id)
        {
            $orderCollection = Mage::getResourceModel('sales/order_grid_collection')->addFieldToFilter('customer_id',Array('eq'=>$customer_id))->setOrder('entity_id', 'desc');
            $limit = 5;
            $orderCollection->getSelect()->limit($limit); 

            foreach($orderCollection as $order){

                $orderListData[] = array(
                    'entity_id'     => $order->getEntityId(),
                    'increment_id'  => $order->getIncrementId(),
                    'store_id'      => $order->getStoreId(),
                    'customer_name' => $order->getBillingName(),
                    'status'        => $order->getStatus(),
                    'order_date'    => date('Y-m-d H:i:s', strtotime($order->getCreatedAt())),
                    'grand_total'   => strip_tags(Mage::helper('core')->currency(Mage::helper('mobileassistant')->getPriceFormat($order->getGrandTotal()))),
                    'toal_qty'      => Mage::getModel('sales/order')->load($order->getEntityId())->getTotalQtyOrdered()
                );
            }
            return $orderListData;
        }


        public function getFilterCustomerListAction()
        {
            if(Mage::helper('mobileassistant')->isEnable()){
                $post_data = Mage::app()->getRequest()->getParams();
                $sessionId = $post_data['session'];
                if (!Mage::getSingleton('api/session')->isLoggedIn($sessionId)) {
                    echo $this->__("The Login has expired. Please try log in again.");
                    return false;
                }
                $search    = $post_data['search_content'];
                $customers = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect('*')->setOrder('entity_id', 'desc');

                if($search != null)
                {
                    $customers->addAttributeToFilter(array(
                            array(
                                'attribute' => 'firstname',
                                'like' => '%'.$search.'%'
                            ),
                            array(
                                'attribute' => 'lastname',
                                'like' => '%'.$search.'%'
                            ),
                            array(
                                'attribute' => 'email',
                                'like' => '%'.$search.'%'
                            )
                        ));
                }


                foreach($customers as $customer){
                    $customer_list[] = array(
                        'entity_id'     => $customer->getEntityId(),
                        'customer_name' => $customer->getFirstname()." ".$customer->getLastname(),
                        'email_id'      => $customer->getEmail()
                    );
                }
                $customerListResultArr = array('customerlistdata' => $customer_list);
                $customerListResult    = Mage::helper('core')->jsonEncode($customerListResultArr);
                return Mage::app()->getResponse()->setBody($customerListResult);
            }else{
                $isEnable    = Mage::helper('core')->jsonEncode(array('enable' => false));
                return Mage::app()->getResponse()->setBody($isEnable);
            }
        }
}