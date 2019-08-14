<?php

class Biztech_Mobileassistant_DashboardController extends Mage_Core_Controller_Front_Action {

    public function dashboardAction() {
        if (Mage::helper('mobileassistant')->isEnable()) {
            $post_data = Mage::app()->getRequest()->getParams();

            $sessionId = '';
            $storeId = '';
            $type_id = '';
            if (isset($post_data['session'])) {
                $sessionId = $post_data['session'];
            }

            if (!$sessionId || $sessionId == NULL) {
                echo $this->__("The Login has expired. Please try log in again.");
                return false;
            }

            if (isset($post_data['storeid'])) {
                $storeId = $post_data['storeid'];
            }
            if (isset($post_data['days_for_dashboard'])) {
                $type_id = $post_data['days_for_dashboard'];
            }

            $now = Mage::getModel('core/date')->timestamp(time());
            $end_date = date('Y-m-d 23:59:59', $now);
            $start_date = '';
            $orderCollection = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('store_id', Array('eq' => $storeId))->addFieldToFilter('status', array('in' => array('complete', 'processing')))->setOrder('entity_id', 'desc');
            if ($type_id == 7) {
                $start_date = date('Y-m-d 00:00:00', strtotime('-6 days'));
            } elseif ($type_id == 30) {
                $start_date = date('Y-m-d 00:00:00', strtotime('-29 days'));
            } elseif ($type_id == 90) {
                $start_date = date('Y-m-d 00:00:00', strtotime('-89 days'));
            } else if ($type_id == 24) {
                $end_date = date("Y-m-d H:m:s");
                $start_date = date("Y-m-d H:m:s", strtotime('-24 hours', time()));
                $timezoneLocal = Mage::app()->getStore()->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE);

                list ($dateStart, $dateEnd) = Mage::getResourceModel('reports/order_collection')
                        ->getDateRange('24h', '', '', true);

                $dateStart->setTimezone($timezoneLocal);
                $dateEnd->setTimezone($timezoneLocal);

                $dates = array();

                while ($dateStart->compare($dateEnd) < 0) {
                    $d = $dateStart->toString('yyyy-MM-dd HH:mm:ss');
                    $dateStart->addHour(1);
                    $dates[] = $d;
                }

                $start_date = $dates[0];
                $end_date = $dates[count($dates) - 1];

                $orderCollection->addAttributeToFilter('created_at', array('from' => $start_date, 'to' => $end_date));
                $total_count = count($orderCollection);
            }

            if ($type_id != 'year') {
                if ($type_id == 'month') {
                    $end_date = date("Y-m-d H:m:s");
                    $start_date = date('Y-m-01 H:m:s');
                }

                if ($type_id != 24) {
                    $orderCollection->addAttributeToFilter('created_at', array('from' => $start_date, 'to' => $end_date));
                    $total_count = count($orderCollection);
                    $dates = $this->getDatesFromRange($start_date, $end_date);
                }
                $count = 0;
                foreach ($dates as $date) {
                    $orderCollectionByDate = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('store_id', Array('eq' => $storeId))->addFieldToFilter('status', array('in' => array('complete', 'processing')))->setOrder('entity_id', 'desc');

                    if ($type_id == 24) {
                        if ($count == 23) {
                            continue;
                        }
                        $dateStart = $dates[$count];
                        $dateEnd = $dates[$count + 1];
                    } else {

                        $dateStart = date('Y-m-d 00:00:00', strtotime($date));
                        $dateEnd = date('Y-m-d 23:59:59', strtotime($date));
                    }
                    $orderByDate = $orderCollectionByDate->addAttributeToFilter('created_at', array('from' => $dateStart, 'to' => $dateEnd));
                    $orderByDate->getSelect()->columns('SUM(grand_total) AS grand_total_sum');
                    $orderByDate->getSelect()->group(array('store_id'));
                    $orderdata = $orderByDate->getData();
                    if (count($orderByDate) == 0) {
                        if ($type_id == 24) {
                            $orderTotalByDate[date("Y-m-d H:i", strtotime($date))] = 0;
                        } else if ($type_id == 'month') {
                            $orderTotalByDate[date('d', strtotime($date))] = 0;
                        } else {
                            $orderTotalByDate[$date] = 0;
                        }
                    } else {
                        if ($type_id == 24) {
                            $ordersByDate[date("Y-m-d H:i", strtotime($date))][] = $orderdata[0]['grand_total_sum'];
                            $orderTotalByDate[date("Y-m-d H:i", strtotime($date))] = array_sum($ordersByDate[date("Y-m-d H:i", strtotime($date))]);
                        } else if ($type_id == 'month') {
                            $ordersByDate[date('d', strtotime($date))][] = $orderdata[0]['grand_total_sum'];
                            $orderTotalByDate[date('d', strtotime($date))] = array_sum($ordersByDate[date('d', strtotime($date))]);
                        } else {
                            $ordersByDate[$date][] = $orderdata[0]['grand_total_sum'];
                            $orderTotalByDate[$date] = array_sum($ordersByDate[$date]);
                        }
                    }

                    $count++;
                }
            } else {
                $end_date = date('Y-m-d');
                $start_date = date('Y-01-01');
                $orderCollection->addAttributeToFilter('created_at', array('from' => $start_date, 'to' => $end_date));
                $total_count = count($orderCollection);
                $months = $this->get_months($start_date, $end_date);
                $current_year = date("Y");
                foreach ($months as $month) {
                    $first_day = $this->firstDay($month, $current_year);
                    $ordersByDate = array();
                    if ($month == date('F'))
                        $last_day = date('Y-m-d');
                    else
                        $last_day = $this->lastday($month, $current_year);

                    $dates = $this->getDatesFromRange($first_day, $last_day);

                    foreach ($dates as $date) {
                        $orderCollectionByDate = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('store_id', Array('eq' => $storeId))->addFieldToFilter('status', array('in' => array('complete', 'processing')))->setOrder('entity_id', 'desc');
                        $dateStart = date('Y-m-d 00:00:00', strtotime($date));
                        $dateEnd = date('Y-m-d 23:59:59', strtotime($date));
                        $orderByDate = $orderCollectionByDate->addAttributeToFilter('created_at', array('from' => $dateStart, 'to' => $dateEnd));
                        $orderByDate->getSelect()->columns('SUM(grand_total) AS grand_total_sum');
                        $orderByDate->getSelect()->group(array('store_id'));
                        $orderdata = $orderByDate->getData();
                        $ordersByDate[] = $orderdata[0]['grand_total_sum'];
                    }

                    $orderTotalByDate[$month] = array_sum($ordersByDate);
                }
            }

            $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();

            $orderGrandTotal = strip_tags(round(array_sum($orderTotalByDate), 2));
            $lifeTimeSales = strip_tags(round(Mage::getResourceModel('reports/order_collection')->addFieldToFilter('store_id', $storeId)->calculateSales()->load()->getFirstItem()->getLifetime(), 2));
            $averageOrder = strip_tags(round(Mage::getResourceModel('reports/order_collection')->addFieldToFilter('store_id', $storeId)->calculateSales()->load()->getFirstItem()->getAverage(), 2));

            if ($type_id == 7) {
                $avg_order_count = $total_count / 7;
                $avg_ordergrandtotal = (array_sum($orderTotalByDate)) / 7;
            } elseif ($type_id == 30) {
                $avg_order_count = $total_count / 30;
                $avg_ordergrandtotal = (array_sum($orderTotalByDate)) / 30;
            } elseif ($type_id == 90) {
                $avg_order_count = $total_count / 90;
                $avg_ordergrandtotal = (array_sum($orderTotalByDate)) / 90;
            } else if ($type_id == 24) {
                $avg_order_count = $total_count / 24;
                $avg_ordergrandtotal = (array_sum($orderTotalByDate)) / 24;
            } else if ($type_id == 'month') {
                $avg_order_count = $total_count / count($dates);
                $avg_ordergrandtotal = (array_sum($orderTotalByDate)) / count($dates);
            } else if ($type_id == 'year') {
                $avg_order_count = $total_count / count($months);
                $avg_ordergrandtotal = (array_sum($orderTotalByDate)) / count($months);
            }

            $avg_order_count = number_format($avg_order_count, 2, '.', ',');
            $avg_ordergrandtotal = strip_tags(round($avg_ordergrandtotal, 2));


            $orderTotalResultArr = array('dashboard_result' => array('ordertotalbydate' => $orderTotalByDate, 'ordergrandtotal' => $orderGrandTotal, 'totalordercount' => $total_count, 'lifetimesales' => $lifeTimeSales, 'averageorder' => $averageOrder, 'avg_order_count' => $avg_order_count, 'avg_order_grandtotal' => $avg_ordergrandtotal));
            $orderDashboardResult = Mage::helper('core')->jsonEncode($orderTotalResultArr);
            return Mage::app()->getResponse()->setBody($orderDashboardResult);
        } else {
            $isEnable = Mage::helper('core')->jsonEncode(array('enable' => false));
            return Mage::app()->getResponse()->setBody($isEnable);
        }
    }

    public function getDatesFromRange($start_date, $end_date) {
        $date_from = strtotime(date('Y-m-d', strtotime($start_date)));
        $date_to = strtotime(date('Y-m-d', strtotime($end_date)));

        for ($i = $date_from; $i <= $date_to; $i+=86400) {
            $dates[] = date("Y-m-d", $i);
        }
        return $dates;
    }

    public function getNewestCustomerAction() {
        if (Mage::helper('mobileassistant')->isEnable()) {
            $post_data = Mage::app()->getRequest()->getParams();

            $sessionId = '';
            $storeId = '';
            if (isset($post_data['session'])) {
                $sessionId = $post_data['session'];
            }

            if (!$sessionId || $sessionId == NULL) {
                echo $this->__("The Login has expired. Please try log in again.");
                return false;
            }

            if (isset($post_data['storeid'])) {
                $storeId = $post_data['storeid'];
            }
            $baseCurrencyCode = (string) Mage::app()->getStore($storeId)->getBaseCurrencyCode();

            $collection = Mage::getResourceModel('reports/customer_collection')->addCustomerName();
            $storeFilter = 0;
            if ($storeId) {
                $collection->addAttributeToFilter('store_id', $storeId);
                $storeFilter = 1;
            }
            $collection->addOrdersStatistics($storeFilter)->orderByCustomerRegistration();

            foreach ($collection as $_collection) {
                $newestCustomer[] = array(
                    'name' => $_collection->getName(),
                    'email' => $_collection->getEmail(),
                    'orders_count' => $_collection->getOrdersCount(),
                    'average_order_amount' => Mage::helper('mobileassistant')->getPriceFormat($_collection->getOrdersAvgAmount()),
                    'total_order_amount' => Mage::helper('mobileassistant')->getPriceFormat($_collection->getOrdersSumAmount())
                );
            }

            $NewestCustomerResult = Mage::helper('core')->jsonEncode($newestCustomer);
            return Mage::app()->getResponse()->setBody($NewestCustomerResult);
        } else {
            $isEnable = Mage::helper('core')->jsonEncode(array('enable' => false));
            return Mage::app()->getResponse()->setBody($isEnable);
        }
    }

    function get_months($date1, $date2) {
        $time1 = strtotime($date1);
        $time2 = strtotime($date2);
        $my = date('mY', $time2);
        $months = array();
        $f = '';

        while ($time1 < $time2) {
            $time1 = strtotime((date('Y-m-d', $time1) . ' +15days'));

            if (date('m', $time1) != $f) {
                $f = date('m', $time1);

                if (date('mY', $time1) != $my && ($time1 < $time2))
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

    function firstDay($month = '', $year = '') {
        if (empty($month)) {
            $month = date('m');
        }
        if (empty($year)) {
            $year = date('Y');
        }
        $result = strtotime("{$year}-{$month}-01");
        return date('Y-m-d', $result);
    }

    protected function getorderetails($storeId, $type_id, $tab = null, $source = null, $limit = null) {
        $orderTotalByDate = array();
        $now = Mage::getModel('core/date')->timestamp(time());
        $end_date = date('Y-m-d 23:59:59', $now);
        $start_date = '';
        $orderListData = array();

        if ($source == 'widget') {
            $orderCollection = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('store_id', Array('eq' => $storeId))->setOrder('entity_id', 'desc');
        } else {
            $orderCollection = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('store_id', Array('eq' => $storeId))->addFieldToFilter('status', Array('eq' => 'complete'))->setOrder('entity_id', 'desc');
        }



        if ($type_id == 7) {
            $start_date = date('Y-m-d 00:00:00', strtotime('-6 days'));
        } elseif ($type_id == 30) {
            $start_date = date('Y-m-d 00:00:00', strtotime('-29 days'));
        } elseif ($type_id == 90) {
            $start_date = date('Y-m-d 00:00:00', strtotime('-89 days'));
        } else if ($type_id == 24) {
            $end_date = date("Y-m-d H:m:s");
            $start_date = date("Y-m-d H:m:s", strtotime('-24 hours', time()));
            $timezoneLocal = Mage::app()->getStore()->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE);

            list ($dateStart, $dateEnd) = Mage::getResourceModel('reports/order_collection')
                    ->getDateRange('24h', '', '', true);

            $dateStart->setTimezone($timezoneLocal);
            $dateEnd->setTimezone($timezoneLocal);

            $dates = array();

            while ($dateStart->compare($dateEnd) < 0) {
                $d = $dateStart->toString('yyyy-MM-dd HH:mm:ss');
                $dateStart->addHour(1);
                $dates[] = $d;
            }

            $start_date = $dates[0];
            $end_date = $dates[count($dates) - 1];

            $orderCollection->addAttributeToFilter('created_at', array('from' => $start_date, 'to' => $end_date));
            $total_count = count($orderCollection);
        }

        if ($type_id != 'year') {
            if ($type_id == 'month') {
                $end_date = date("Y-m-d H:m:s");
                $start_date = date('Y-m-01 H:m:s');
            }

            if ($type_id != 24) {
                $orderCollection->addAttributeToFilter('created_at', array('from' => $start_date, 'to' => $end_date));
                $total_count = count($orderCollection);
                $dates = $this->getDatesFromRange($start_date, $end_date);
            }
            $count = 0;
            foreach ($dates as $date) {
                if ($source == 'widget') {
                    $orderCollectionByDate = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('store_id', Array('eq' => $storeId))->setOrder('entity_id', 'desc');
                } else {
                    $orderCollectionByDate = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('store_id', Array('eq' => $storeId))->addFieldToFilter('status', Array('eq' => 'complete'))->setOrder('entity_id', 'desc');
                }


                if ($type_id == 24) {
                    if ($count == 23) {
                        continue;
                    }
                    $dateStart = $dates[$count];
                    $dateEnd = $dates[$count + 1];
                } else {

                    $dateStart = date('Y-m-d 00:00:00', strtotime($date));
                    $dateEnd = date('Y-m-d 23:59:59', strtotime($date));
                }
                $orderByDate = $orderCollectionByDate->addAttributeToFilter('created_at', array('from' => $dateStart, 'to' => $dateEnd));
                $orderByDate->getSelect()->columns('SUM(grand_total) AS grand_total_sum');
                $orderByDate->getSelect()->group(array('store_id'));
                $orderdata = $orderByDate->getData();
                if (count($orderByDate) == 0) {
                    if ($type_id == 24) {
                        $orderTotalByDate[date("Y-m-d H:i", strtotime($date))] = 0;
                    } else if ($type_id == 'month') {
                        $orderTotalByDate[date('d', strtotime($date))] = 0;
                    } else {
                        $orderTotalByDate[$date] = 0;
                    }
                } else {
                    if ($type_id == 24) {
                        $ordersByDate[date("Y-m-d H:i", strtotime($date))][] = $orderdata[0]['grand_total_sum'];
                        $orderTotalByDate[date("Y-m-d H:i", strtotime($date))] = array_sum($ordersByDate[date("Y-m-d H:i", strtotime($date))]);
                    } else if ($type_id == 'month') {
                        $ordersByDate[date('d', strtotime($date))][] = $orderdata[0]['grand_total_sum'];
                        $orderTotalByDate[date('d', strtotime($date))] = array_sum($ordersByDate[date('d', strtotime($date))]);
                    } else {
                        $ordersByDate[$date][] = $orderdata[0]['grand_total_sum'];
                        $orderTotalByDate[$date] = array_sum($ordersByDate[$date]);
                    }
                }

                $count++;
            }
        } else {
            $end_date = date('Y-m-d');
            $start_date = date('Y-01-01');
            $orderCollection->addAttributeToFilter('created_at', array('from' => $start_date, 'to' => $end_date));
            $total_count = count($orderCollection);
            $months = $this->get_months($start_date, $end_date);
            $current_year = date("Y");
            foreach ($months as $month) {
                $first_day = $this->firstDay($month, $current_year);
                $ordersByDate = array();
                if ($month == date('F'))
                    $last_day = date('Y-m-d');
                else
                    $last_day = $this->lastday($month, $current_year);

                $dates = $this->getDatesFromRange($first_day, $last_day);

                foreach ($dates as $date) {

                    if ($source == 'widget') {
                        $orderCollectionByDate = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('store_id', Array('eq' => $storeId))->setOrder('entity_id', 'desc');
                    } else {
                        $orderCollectionByDate = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('store_id', Array('eq' => $storeId))->addFieldToFilter('status', Array('eq' => 'complete'))->setOrder('entity_id', 'desc');
                    }
                    $dateStart = date('Y-m-d 00:00:00', strtotime($date));
                    $dateEnd = date('Y-m-d 23:59:59', strtotime($date));
                    $orderByDate = $orderCollectionByDate->addAttributeToFilter('created_at', array('from' => $dateStart, 'to' => $dateEnd));
                    $orderByDate->getSelect()->columns('SUM(grand_total) AS grand_total_sum');
                    $orderByDate->getSelect()->group(array('store_id'));
                    $orderdata = $orderByDate->getData();
                    $ordersByDate[] = $orderdata[0]['grand_total_sum'];
                }

                $orderTotalByDate[$month] = array_sum($ordersByDate);
            }
        }


        $result = array('ordertotalbydate' => $orderTotalByDate, 'ordertotalcount' => $total_count);

        $orderCollection->getSelect()->limit($limit);

        if ($tab == 'order') {

            foreach ($orderCollection->getData() as $_order) {
                $grand_total = Mage::helper('mobileassistant')->getPrice($_order['grand_total'], $_order['store_id'], $_order['order_currency_code']);


                $orderListData[] = array(
                    'entity_id' => $_order['entity_id'],
                    'increment_id' => $_order['increment_id'],
                    'store_id' => $_order['store_id'],
                    'customer_name' => $_order['customer_firstname'] . ' ' . $_order['customer_lastname'],
                    'status' => $_order['status'],
                    'order_date' => Mage::helper('mobileassistant')->getActualOrderDate($_order['create_at']),
                    'grand_total' => $grand_total,
                    'toal_qty' => Mage::getModel('sales/order')->load($_order['entity_id'])->getTotalQtyOrdered(),
                    'total_items' => $_order['total_item_count']
                );
            }

            $result['ordercollection'] = $orderListData;
        }

        return $result;
    }

    protected function getcustomerdetails($storeId, $type_id, $limit = null, $tab = null) {
        $collection = Mage::getResourceModel('reports/customer_collection')->addCustomerName();
        $storeFilter = 0;
        if ($storeId) {
            $collection->addAttributeToFilter('store_id', $storeId);
            $storeFilter = 1;
        }
        $collection->addOrdersStatistics($storeFilter)->orderByCustomerRegistration();
        $total = $collection->getSize();

        if (isset($type_id) && $type_id != null) {
            if ($type_id == 7) {
                $start_date = date('Y-m-d 00:00:00', strtotime('-6 days'));
            } elseif ($type_id == 30) {
                $start_date = date('Y-m-d 00:00:00', strtotime('-29 days'));
            } elseif ($type_id == 90) {
                $start_date = date('Y-m-d 00:00:00', strtotime('-89 days'));
            } else if ($type_id == 24) {
                $end_date = date("Y-m-d H:m:s");
                $start_date = date("Y-m-d H:m:s", strtotime('-24 hours', time()));
                $timezoneLocal = Mage::app()->getStore()->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE);

                list ($dateStart, $dateEnd) = Mage::getResourceModel('reports/order_collection')
                        ->getDateRange('24h', '', '', true);

                $dateStart->setTimezone($timezoneLocal);
                $dateEnd->setTimezone($timezoneLocal);

                $dates = array();

                while ($dateStart->compare($dateEnd) < 0) {
                    $d = $dateStart->toString('yyyy-MM-dd HH:mm:ss');
                    $dateStart->addHour(1);
                    $dates[] = $d;
                }

                $start_date = $dates[0];
                $end_date = $dates[count($dates) - 1];

                $collection->addAttributeToFilter('created_at', array('from' => $start_date, 'to' => $end_date));
                $total_count = count($collection);
            }

            if ($type_id != 'year') {
                if ($type_id == 'month') {
                    $end_date = date("Y-m-d H:m:s");
                    $start_date = date('Y-m-01 H:m:s');
                }

                if ($type_id != 24) {
                    $collection->addAttributeToFilter('created_at', array('from' => $start_date, 'to' => $end_date));
                    $total_count = count($collection);
                    $dates = $this->getDatesFromRange($start_date, $end_date);
                }
            } else {
                $end_date = date('Y-m-d');
                $start_date = date('Y-01-01');
                $collection->addAttributeToFilter('created_at', array('from' => $start_date, 'to' => $end_date));
                $total_count = count($collection);
            }
        }
        if ($tab == 'customer' && isset($limit)) {
            $collection->clear();
            $collection->getSelect()->limit($limit);
        }
        $resultWidget = array('customercollection' => $collection, 'customertotal' => $total_count);
        return $resultWidget;
    }

    protected function getlowstockproducts($tab, $storeId, $limit) {
        $product_list = array();

        $products = Mage::getModel('catalog/product')->getCollection()->addStoreFilter($storeId)->setOrder('entity_id', 'desc')
                        ->joinField(
                                'is_in_stock', 'cataloginventory/stock_item', 'is_in_stock', 'product_id=entity_id', '{{table}}.stock_id=1', 'left'
                        )->addAttributeToFilter('is_in_stock', array('eq' => 0));

        $productResultArr['totalproducts'] = $products->getSize();

        $products->getSelect()->limit($limit);
        if ($tab == 'product') {
            foreach ($products as $product) {
                $product_data = Mage::getModel('catalog/product')->load($product->getId());
                $status = $product_data->getStatus();
                $qty = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product_data)->getQty();
                if ($status == 1) {
                    $status = 'Enabled';
                } else {
                    $status = 'Disabled';
                }
                $product_list[] = array(
                    'id' => $product->getId(),
                    'sku' => $product_data->getSku(),
                    'name' => $product_data->getName(),
                    'status' => $status,
                    'qty' => $qty,
                    'price' => Mage::helper('mobileassistant')->getPrice($product_data->getPrice(), $storeId, Mage::app()->getStore()->getCurrentCurrencyCode()),
                    'image' => ($product_data->getImage()) ? Mage::helper('catalog/image')->init($product, 'image', $product_data->getImage())->resize(300, 330)->keepAspectRatio(true)->constrainOnly(true)->__toString() : 'N/A',
                    'type' => $product->getTypeId()
                );
            }
        }

        $productResultArr['productlistdata'] = $product_list;

        return $productResultArr;
    }

    public function getwidgetdetailsAction() {
        if (Mage::helper('mobileassistant')->isEnable()) {
            $post_data = Mage::app()->getRequest()->getParams();

            $sessionId = '';
            $storeId = '';
            $type_id = '';
            $tab = '';
            $limit = '';
            $newestCustomer = array();
            if (isset($post_data['session'])) {
                $sessionId = $post_data['session'];
            }
            if (!$sessionId || $sessionId == NULL) {
                echo $this->__("The Login has expired. Please try log in again.");
                return false;
            }

            if (isset($post_data['storeid'])) {
                $storeId = $post_data['storeid'];
            }
            if (isset($post_data['days_for_dashboard'])) {
                $type_id = $post_data['days_for_dashboard'];
            }
            if (isset($post_data['tab'])) {
                $tab = $post_data['tab'];
            }
            if (isset($post_data['limit'])) {
                $limit = $post_data['limit'];
            }
            $source = 'widget';

            $baseCurrencyCode = (string) Mage::app()->getStore($storeId)->getBaseCurrencyCode();

            /* order detail */
            $orderDetails = $this->getorderetails($storeId, $type_id, $tab, $source, $limit);
            $orderTotalByDate = $orderDetails['ordertotalbydate'];
            $orderGrandTotal = strip_tags(array_sum($orderTotalByDate));


            $widgetResultArr['widget_order'] = array('ordergrandtotal' => $orderGrandTotal, 'totalordercount' => $orderDetails['ordertotalcount'], 'ordercollection' => $orderDetails['ordercollection']);

            /* customer detail */
            $customerDetails = $this->getcustomerdetails($storeId, $type_id, $limit, $tab);
            $customertotal = $customerDetails['customertotal'];

            if ($tab == 'customer') {

                $collection = $customerDetails['customercollection'];

                foreach ($collection as $_collection) {
                    $newestCustomer[] = array(
                        'entity_id' => $_collection->getEntityId(),
                        'name' => $_collection->getName(),
                        'email' => $_collection->getEmail(),
                    );
                }
            }

            $widgetResultArr['widget_customer'] = array('totalcustomer' => $customertotal, 'customers_detail' => $newestCustomer);

            /* products detail */
            $productDetails = $this->getlowstockproducts($tab, $storeId, $limit);
            $totalProduct = $productDetails['totalproducts'];
            $productdata = $productDetails['productlistdata'];

            $widgetResultArr['widget_product'] = array('totalproduct' => $totalProduct, 'product_detail' => $productdata);

            $widgetResult = Mage::helper('core')->jsonEncode($widgetResultArr);
            return Mage::app()->getResponse()->setBody($widgetResult);
        } else {
            $isEnable = Mage::helper('core')->jsonEncode(array('enable' => false));
            return Mage::app()->getResponse()->setBody($isEnable);
        }
    }

    public function sendfeedbackAction() {
        if (Mage::helper('mobileassistant')->isEnable()) {
            $post_data = Mage::app()->getRequest()->getParams();

            $sessionId = '';
            if (isset($post_data['session'])) {
                $sessionId = $post_data['session'];
            }
            if (!$sessionId || $sessionId == NULL) {
                echo $this->__("The Login has expired. Please try log in again.");
                return false;
            }

            $to = "support@biztechconsultancy.com";
            $subject = "Feedback For MobileAssistant";

            $message = "<div>
                <p>Hi there. This is just test email.</p>
                <p>Below is ratings given by user: " . $post_data['nickname'] . "</p>
                <p>Ratings: " . $post_data['rating'] . "</p>" . " \r\n" . "
                <p>Comments: " . $post_data['comment'] . "</p>" . " \r\n" . "
                <p>Please find application link:</p>" . " \r\n" . "
                <a href='" . Mage::getBaseUrl() . "'>Click here</a>" . " \r\n" . "
                </div>";

            $header = "From:" . Mage::getStoreConfig('trans_email/ident_general/email') . " \r\n";
            $header .= "MIME-Version: 1.0\r\n";
            $header .= "Content-type: text/html\r\n";
            $retval = mail($to, $subject, $message, $header);
            if ($retval == true) {
                $result = array('status' => true, 'message' => 'Message sent successfully');
            } else {
                $result = array('status' => false, 'message' => 'Message could not be sent');
            }

            $NewestCustomerResult = Mage::helper('core')->jsonEncode($result);
            return Mage::app()->getResponse()->setBody($NewestCustomerResult);
        } else {
            $isEnable = Mage::helper('core')->jsonEncode(array('enable' => false));
            return Mage::app()->getResponse()->setBody($isEnable);
        }
    }

}
