<?php
    class Biztech_Mobileassistant_OrderController extends Mage_Core_Controller_Front_Action
    {
        public function getOrderListAction()
        {
            if(Mage::helper('mobileassistant')->isEnable()){
                $post_data = Mage::app()->getRequest()->getParams();
                $sessionId = $post_data['session'];
                if (!Mage::getSingleton('api/session')->isLoggedIn($sessionId)) {
                    echo $this->__("The Login has expired. Please try log in again.");
                    return false;
                }

                $limit      = $post_data['limit'];
                $storeId    = $post_data['storeid'];
                $offset     = $post_data['offset'];
                $is_refresh = $post_data['is_refresh'];

                $orderCollection = Mage::getResourceModel('sales/order_grid_collection')->addFieldToFilter('store_id',Array('eq'=>$storeId))->setOrder('entity_id', 'desc');
                if($offset != null){
                    $orderCollection->addAttributeToFilter('entity_id', array('lt' => $offset));
                }
                if($is_refresh == 1){
                    $last_fetch_order  = $post_data['last_fetch_order'];
                    $min_fetch_order   = $post_data['min_fetch_order'];
                    $last_updated      = $post_data['last_updated'];

                    $orderCollection->getSelect()->where("(entity_id BETWEEN '".$min_fetch_order."'AND '".$last_fetch_order ."' AND updated_at > '".$last_updated."') OR entity_id >'".$last_fetch_order."'");
                }
                $orderCollection->getSelect()->limit($limit);

                foreach($orderCollection as $order){

                    $orderListData[] = array(
                        'entity_id'     => $order->getEntityId(),
                        'increment_id'  => $order->getIncrementId(),
                        'store_id'      => $order->getStoreId(),
                        'customer_name' => $order->getBillingName(),
                        'status'        => $order->getStatus(),
                        'order_date'    => date('Y-m-d H:i:s', strtotime($order->getCreatedAt())),
                        'grand_total'   => strip_tags(Mage::helper('core')->currency($order->getGrandTotal())),
                        'toal_qty'      => Mage::getModel('sales/order')->load($order->getEntityId())->getTotalQtyOrdered()
                    );
                }

                $updated_time       = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));
                $orderListResultArr = array('orderlistdata' => $orderListData,'updated_time' =>$updated_time);
                $orderListResult    = Mage::helper('core')->jsonEncode($orderListResultArr);
                return Mage::app()->getResponse()->setBody($orderListResult);
            }else{
                $isEnable    = Mage::helper('core')->jsonEncode(array('enable' => false));
                return Mage::app()->getResponse()->setBody($isEnable);
            }
        }

        public function getFilterOrderListAction()
        {
            if(Mage::helper('mobileassistant')->isEnable()){
                $post_data = Mage::app()->getRequest()->getParams();
                $storeId   = $post_data['storeid'];
                $sessionId = $post_data['session'];

                if (!Mage::getSingleton('api/session')->isLoggedIn($sessionId)) {
                    echo $this->__("The Login has expired. Please try log in again.");
                    return false;
                }
                $filter_by_date   = $post_data['filter_by_date'];
                $filter_by_status = $post_data['filter_by_status'];
                $search_by_id     = $post_data['search_by_id'];
                $now              = Mage::getModel('core/date')->timestamp(time());
                $orderCollection  = Mage::getResourceModel('sales/order_grid_collection')->addFieldToFilter('store_id',Array('eq'=>$storeId))->setOrder('entity_id', 'desc');

                if($filter_by_date != null){
                    $dateEnd   = date('Y-m-d 23:59:59', $now); 
                    if($filter_by_date == 1){
                        $dateStart = date('Y-m-d 00:00:00', $now);
                        $orderCollection->addAttributeToFilter('created_at', array('from'=>$dateStart, 'to'=>$dateEnd));
                    }elseif($filter_by_date == 2){
                        $dateStart = date('Y-m-d 00:00:00', strtotime('-6 days'));
                        $orderCollection->addAttributeToFilter('created_at', array('from'=>$dateStart, 'to'=>$dateEnd));
                    }elseif($filter_by_date == 3){
                        $dateStart = date('Y-m-01 00:00:00');
                        $orderCollection->addAttributeToFilter('created_at', array('from'=>$dateStart, 'to'=>$dateEnd));
                    }elseif($filter_by_date == 4){
                        $orderCollection->addAttributeToFilter('created_at', array("lt"=>$post_data['before_date']." 00:00:00"));
                    }elseif($filter_by_date == 5){
                        $dateStart = $post_data['start_date']." 00:00:00";
                        $dateEnd   = $post_data['end_date']." 23:59:59"; 
                        $orderCollection->addAttributeToFilter('created_at', array('from'=>$dateStart, 'to'=>$dateEnd));
                    }elseif($filter_by_date == 6){
                        $orderCollection->addAttributeToFilter('created_at', array("gt"=>$post_data['after_date']." 23:59:59"));
                    }
                }
                if($filter_by_status != null){
                    $orderCollection->addFieldToFilter('status',Array('eq'=>$filter_by_status));
                }

                if($search_by_id != null){
                    $orderCollection->addFieldToFilter('increment_id',Array('like'=>'%'.$search_by_id.'%'));
                }
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
                $orderListResultArr = array('orderlistdata' => $orderListData);
                $orderListResult    = Mage::helper('core')->jsonEncode($orderListResultArr);
                return Mage::app()->getResponse()->setBody($orderListResult);
            }else{
                $isEnable    = Mage::helper('core')->jsonEncode(array('enable' => false));
                return Mage::app()->getResponse()->setBody($isEnable);
            }
        }

        public function getOrderDetailAction()
        {
            if(Mage::helper('mobileassistant')->isEnable()){
                $post_data = Mage::app()->getRequest()->getParams();
                $sessionId = $post_data['session'];
                if (!Mage::getSingleton('api/session')->isLoggedIn($sessionId)) {
                    echo $this->__("The Login has expired. Please try log in again.");
                    return false;
                }

                $order_id = $post_data['entity_id'];
                $order    = Mage::getModel('sales/order')->load($order_id);

                $order_detail = array(
                    'entity_id'    => $order->getEntityId(),
                    'increment_id' => $order->getIncrementId(),
                    'status'       => $order->getStatus(),
                    'order_date'   => date('Y-m-d H:i:s', strtotime($order->getCreatedAt())),
                    'total_qty'    => $order->getTotalQtyOrdered(),
                    'grand_total'  => strip_tags(Mage::helper('core')->currency(Mage::helper('mobileassistant')->getPriceFormat($order->getGrandTotal())))
                );

                $customer_id   = $order->getCustomerId();
                $customer_name = $order->getCustomerFirstname()." ".$order->getCustomerLastname();
                if($customer_id == null){$customer_name = $order->getCustomerName();}
                $customer_detail = array(
                    'customer_id'    => $customer_id,
                    'customer_name'  => $customer_name,
                    'customer_email' => $order->getCustomerEmail()
                );

                $billing_address = $order->getBillingAddress();
                $billing_address_data = array(
                    'name'      => $billing_address->getFirstname().' '.$billing_address->getLastname(),
                    'street'    => $billing_address->getData('street'),
                    'city'      => $billing_address->getCity(),
                    'region'    => $billing_address->getRegion(),
                    'postcode'  => $billing_address->getPostcode(),
                    'country'   => Mage::getModel('directory/country')->loadByCode($billing_address->getCountryId())->getName(),
                    'telephone' => $billing_address->getTelephone()
                );
                $shipping_address = $order->getShippingAddress();
                $shipping_address_data = array(
                    'name'      => $shipping_address->getFirstname().' '.$shipping_address->getLastname(),
                    'street'    => $shipping_address->getData('street'),
                    'city'      => $shipping_address->getCity(),
                    'region'    => $shipping_address->getRegion(),
                    'postcode'  => $shipping_address->getPostcode(),
                    'country'   => Mage::getModel('directory/country')->loadByCode($shipping_address->getCountryId())->getName(),
                    'telephone' => $shipping_address->getTelephone()
                );

                $payment_info = array(
                    'payment_method' => $order->getPayment()->getMethodInstance()->getTitle()
                );

                $shipping_info = array(
                    'shipping_method' => $order->getShippingDescription(),
                    'shipping_charge' => strip_tags(Mage::helper('core')->currency(Mage::helper('mobileassistant')->getPriceFormat($order->getShippingAmount())))
                );

                $products_detail = $this->_orderedProductDetails($order_id);

                $full_order_detail = array(
                    'basic_order_detail' => $order_detail,
                    'customer_detail'    => $customer_detail,
                    'billing_address'    => $billing_address_data,
                    'shipping_address'   => $shipping_address_data,
                    'payment_info'       => $payment_info,
                    'shipping_info'      => $shipping_info,
                    'product_detail'     => $products_detail               
                );
                $orderDetailResultArr = array('orderlistdata' => $full_order_detail);
                $orderDetailResult    = Mage::helper('core')->jsonEncode($orderDetailResultArr);
                return Mage::app()->getResponse()->setBody($orderDetailResult);
            }else{
                $isEnable    = Mage::helper('core')->jsonEncode(array('enable' => false));
                return Mage::app()->getResponse()->setBody($isEnable);
            }
        }

        protected function _orderedProductDetails($order_id)
        {
            $order = Mage::getModel('sales/order')->load($order_id);
            foreach ($order->getItemsCollection() as $item) {
                $product = null;
                if ($item->getParentItem()) continue;
                if ($_options = $this->_getItemOptions($item)) {
                    $skus = $_options;
                }
                $products_detail[] = array(
                    'product_id'  => $item->getProductId(),
                    'name'        => $item->getName(),
                    'sku'         => $item->getSku(),
                    'unit_price'  => strip_tags(Mage::helper('core')->currency(Mage::helper('mobileassistant')->getPriceFormat($item->getOriginalPrice()))),
                    'ordered_qty' => round($item->getQtyOrdered(), 2),
                    'row_total'   => strip_tags(Mage::helper('core')->currency(Mage::helper('mobileassistant')->getPriceFormat($item->getRowTotal()))),
                    'options'     => $skus
                );
            }
            return $products_detail; 
        }

        private function _getItemOptions($item)
        {
            $id = array('id' => $item->getItemId());
            $order_items = Mage::getModel('sales/order_item')->getCollection()->addFieldToFilter('parent_item_id',Array('eq'=>$id));
            foreach($order_items as $order_item)
            {
                $product_data = Mage::getModel('catalog/product')->load($order_item->getProductId());
                $skus[] = $product_data->getSku();
            }
            return $skus;
        }
}