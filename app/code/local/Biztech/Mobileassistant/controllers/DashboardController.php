<?php
    class Biztech_Mobileassistant_DashboardController extends Mage_Core_Controller_Front_Action
    {
        public function dashboardAction()
        {
            if(Mage::helper('mobileassistant')->isEnable()){
                $post_data = Mage::app()->getRequest()->getParams();
                $sessionId = $post_data['session'];
                if (!Mage::getSingleton('api/session')->isLoggedIn($sessionId)) {
                    echo $this->__("The Login has expired. Please try log in again.");
                    return false;
                }

                $storeId  = $post_data['storeid'];
                $type_id  = $post_data['days_for_dashboard'];
                $now      = Mage::getModel('core/date')->timestamp(time());
                $end_date = date('Y-m-d 23:59:59', $now); 
                $orderCollection  = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('store_id',Array('eq'=>$storeId))->addFieldToFilter('status',Array('eq'=>'complete'))->setOrder('entity_id', 'desc');            
                if($type_id == 7){
                    $start_date = date('Y-m-d 00:00:00', strtotime('-6 days'));
                }elseif($type_id == 30){
                    $start_date = date('Y-m-d 00:00:00', strtotime('-29 days'));
                }elseif($type_id == 90){
                    $start_date = date('Y-m-d 00:00:00', strtotime('-89 days'));
                }
                $orderCollection->addAttributeToFilter('created_at', array('from'=>$start_date, 'to'=>$end_date));
                $total_count = count($orderCollection);
                $dates       = $this->getDatesFromRange($start_date, $end_date);
                foreach($dates as $date)
                {
                    $orderCollectionByDate = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('store_id',Array('eq'=>$storeId))->addFieldToFilter('status',Array('eq'=>'complete'))->setOrder('entity_id', 'desc');
                    $dateStart   = date('Y-m-d 00:00:00',strtotime($date));
                    $dateEnd     = date('Y-m-d 23:59:59',strtotime($date)); 
                    $orderByDate = $orderCollectionByDate->addAttributeToFilter('created_at', array('from'=>$dateStart, 'to'=>$dateEnd));
                    if(count($orderByDate) == 0)
                    {
                        $orderTotalByDate[$date] = 0; 
                    }
                    else{
                        foreach($orderByDate as $order){
                            $ordersByDate[$date][]   = $order->getGrandTotal();
                            $orderTotalByDate[$date] = array_sum($ordersByDate[$date]);
                        }
                    }
                }

                $orderGrandTotal      = strip_tags(Mage::helper('core')->currency(array_sum($orderTotalByDate)));
                $lifeTimeSales        = strip_tags(Mage::helper('core')->currency(round(Mage::getResourceModel('reports/order_collection')->addFieldToFilter('store_id', $storeId)->calculateSales()->load()->getFirstItem()->getLifetime(),2)));
                $averageOrder         = strip_tags(Mage::helper('core')->currency(round(Mage::getResourceModel('reports/order_collection')->addFieldToFilter('store_id', $storeId)->calculateSales()->load()->getFirstItem()->getAverage(),2)));
                $orderTotalResultArr  = array('dashboard_result' =>array('ordertotalbydate' => $orderTotalByDate,'ordergrandtotal' => $orderGrandTotal,'totalordercount' => $total_count,'lifetimesales' => $lifeTimeSales,'averageorder' => $averageOrder));
                $orderDashboardResult = Mage::helper('core')->jsonEncode($orderTotalResultArr);
                return Mage::app()->getResponse()->setBody($orderDashboardResult);
            }else{
                $isEnable    = Mage::helper('core')->jsonEncode(array('enable' => false));
                return Mage::app()->getResponse()->setBody($isEnable);
            }
        }

        public function getDatesFromRange($start_date, $end_date)
        {
            $date_from = strtotime(date('Y-m-d', strtotime($start_date)));
            $date_to   = strtotime(date('Y-m-d', strtotime($end_date))); 

            for ($i=$date_from; $i<=$date_to; $i+=86400) {  
                $dates[] = date("Y-m-d", $i);  
            }  
            return $dates;
        }
}