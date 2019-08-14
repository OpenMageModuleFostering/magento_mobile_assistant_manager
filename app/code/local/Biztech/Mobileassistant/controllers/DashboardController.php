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
                $start_date = '';
                $orderCollection  = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('store_id',Array('eq'=>$storeId))->addFieldToFilter('status',Array('eq'=>'complete'))->setOrder('entity_id', 'desc');            
                if($type_id == 7){
                    $start_date = date('Y-m-d 00:00:00', strtotime('-6 days'));
                }elseif($type_id == 30){
                    $start_date = date('Y-m-d 00:00:00', strtotime('-29 days'));
                }elseif($type_id == 90){
                    $start_date = date('Y-m-d 00:00:00', strtotime('-89 days'));
                } else if ($type_id == 24){
                    $end_date = date("Y-m-d H:m:s");
                    $start_date = date("Y-m-d H:m:s", strtotime('-24 hours', time()));
                    $timezoneLocal = Mage::app()->getStore()->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE);

                    list ($dateStart, $dateEnd) = Mage::getResourceModel('reports/order_collection')
                    ->getDateRange('24h', '', '', true);

                    $dateStart->setTimezone($timezoneLocal);
                    $dateEnd->setTimezone($timezoneLocal);

                    $dates = array();

                    while($dateStart->compare($dateEnd) < 0){
                        $d = $dateStart->toString('yyyy-MM-dd HH:mm:ss');
                        $dateStart->addHour(1);
                        $dates[] = $d;
                    }

                    $start_date = $dates[0];
                    $end_date   = $dates[count($dates)-1];

                    $orderCollection->addAttributeToFilter('created_at', array('from'=>$start_date, 'to'=>$end_date));
                    $total_count = count($orderCollection);
                } 

                if ($type_id!='year'){
                    if ($type_id=='month'){
                        $end_date = date("Y-m-d H:m:s");
                        $start_date = date('Y-m-01 H:m:s');
                    }

                    if ($type_id!=24){
                        $orderCollection->addAttributeToFilter('created_at', array('from'=>$start_date, 'to'=>$end_date));
                        $total_count = count($orderCollection);
                        $dates       = $this->getDatesFromRange($start_date, $end_date);
                    }
                    $count = 0;
                    foreach($dates as $date)
                    {
                        $orderCollectionByDate = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('store_id',Array('eq'=>$storeId))->addFieldToFilter('status',Array('eq'=>'complete'))->setOrder('entity_id', 'desc');

                        if ($type_id==24){
                            $dateStart   = $dates[$count];
                            $dateEnd     = $dates[$count+1]; 
                        }else{

                            $dateStart   = date('Y-m-d 00:00:00',strtotime($date));
                            $dateEnd     = date('Y-m-d 23:59:59',strtotime($date)); 
                        }
                        $orderByDate = $orderCollectionByDate->addAttributeToFilter('created_at', array('from'=>$dateStart, 'to'=>$dateEnd));
                        $orderByDate->getSelect()->columns('SUM(grand_total) AS grand_total_sum');
                        $orderByDate->getSelect()->group(array('store_id'));
                        $orderdata= $orderByDate->getData();
                        if(count($orderByDate) == 0)
                        {
                            if ($type_id==24){
                                $orderTotalByDate[date("Y-m-d H:i",strtotime($date))] = 0;
                            }else if ($type_id=='month'){
                                $orderTotalByDate[date('d',strtotime($date))] = 0;
                            }else{
                                $orderTotalByDate[$date] = 0; 
                            }
                        }
                        else{
                            if ($type_id==24){
                                $ordersByDate[date("Y-m-d H:i",strtotime($date))][]   = $orderdata[0]['grand_total_sum'];
                                $orderTotalByDate[date("Y-m-d H:i",strtotime($date))] = array_sum($ordersByDate[date("Y-m-d H:i",strtotime($date))]);    
                            }else if ($type_id=='month'){
                                $ordersByDate[date('d',strtotime($date))][]   = $orderdata[0]['grand_total_sum'];
                                $orderTotalByDate[date('d',strtotime($date))] = array_sum($ordersByDate[date('d',strtotime($date))]);    
                            }else{
                                $ordersByDate[$date][]   = $orderdata[0]['grand_total_sum'];
                                $orderTotalByDate[$date] = array_sum($ordersByDate[$date]);    
                            }


                        }

                        $count++;
                    }
                }else{
                    $end_date = date ('Y-m-d');
                    $start_date = date ('Y-01-01');
                    $orderCollection->addAttributeToFilter('created_at', array('from'=>$start_date, 'to'=>$end_date));
                    $total_count = count($orderCollection);
                    $months = $this->get_months($start_date, $end_date);
                    $current_year = date("Y");
                    foreach ($months as $month){
                        $first_day = $this->firstDay($month,$current_year);
                        $ordersByDate = array();
                        if ($month==date('F'))
                            $last_day = date ('Y-m-d');
                        else
                            $last_day = $this->lastday($month,$current_year);

                        $dates       = $this->getDatesFromRange($first_day, $last_day);

                        foreach($dates as $date)
                        {
                            $orderCollectionByDate = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('store_id',Array('eq'=>$storeId))->addFieldToFilter('status',Array('eq'=>'complete'))->setOrder('entity_id', 'desc');
                            $dateStart   = date('Y-m-d 00:00:00',strtotime($date));
                            $dateEnd     = date('Y-m-d 23:59:59',strtotime($date)); 
                            $orderByDate = $orderCollectionByDate->addAttributeToFilter('created_at', array('from'=>$dateStart, 'to'=>$dateEnd));
                            $orderByDate->getSelect()->columns('SUM(grand_total) AS grand_total_sum');
                            $orderByDate->getSelect()->group(array('store_id'));
                            $orderdata= $orderByDate->getData();
                            $ordersByDate[]   = $orderdata[0]['grand_total_sum'];
                        }

                        $orderTotalByDate[$month] = array_sum($ordersByDate);
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

        function get_months($date1, $date2) { 
            $time1 = strtotime($date1); 
            $time2 = strtotime($date2); 
            $my = date('mY', $time2); 
            $months = array(); 
            $f = ''; 

            while($time1 < $time2) { 
                $time1 = strtotime((date('Y-m-d', $time1).' +15days')); 

                if(date('m', $time1) != $f) { 
                    $f = date('m', $time1); 

                    if(date('mY', $time1) != $my && ($time1 < $time2)) 
                        $months[] = date('m', $time1); 
                } 

            } 

            $months[] = date('m', $time2); 
            return $months; 
        } 

        function lastday($month = '', $year = '') {
            if (empty($month)) {
                $month = date('m');
            }
            if (empty($year)) {
                $year = date('Y');
            }
            $result = strtotime("{$year}-{$month}-01");
            $result = strtotime('-1 day', strtotime('+1 month', $result));
            return date('Y-m-d', $result);
        }

        function firstDay($month = '', $year = '')
        {
            if (empty($month)) {
                $month = date('m');
            }
            if (empty($year)) {
                $year = date('Y');
            }
            $result = strtotime("{$year}-{$month}-01");
            return date('Y-m-d', $result);
        }
}