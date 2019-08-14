<?php
    class Biztech_Mobileassistant_ProductController extends Mage_Core_Controller_Front_Action
    {
        public function getProductListAction()
        {
            if(Mage::helper('mobileassistant')->isEnable()){
                $post_data = Mage::app()->getRequest()->getParams();
                $sessionId = $post_data['session'];
                if (!Mage::getSingleton('api/session')->isLoggedIn($sessionId)) {
                    echo $this->__("The Login has expired. Please try log in again.");
                    return false;
                }

                $storeId   = $post_data['storeid'];
                $limit     = $post_data['limit'];
                $offset    = $post_data['offset'];
                $new_products    = $post_data['last_fetch_product'];
                $is_refresh = $post_data['is_refresh'];

                $products  = Mage::getModel('catalog/product')->getCollection()->addStoreFilter($storeId)->setOrder('entity_id', 'desc');
                if($offset != null){
                    $products->addAttributeToFilter('entity_id', array('lt' => $offset));
                }
                

                if($is_refresh == 1){
                    $last_fetch_product     =   $post_data['last_fetch_product'];
                    $min_fetch_product      =   $post_data['min_fetch_product'];
                    $last_updated           =   $post_data['last_updated'];
                    $products->getSelect()->where("(entity_id BETWEEN '".$min_fetch_product."'AND '".$last_fetch_product ."' AND updated_at > '".$last_updated."') OR entity_id >'".$last_fetch_product."'");
                }

                $products->getSelect()->limit($limit);

                foreach($products as $product)
                {
                    $product_data = Mage::getModel('catalog/product')->load($product->getId());
                    $status       = $product_data->getStatus();
                    $qty          = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product_data)->getQty();
                    if($status == 1){$status = 'Enabled';}else{$status = 'Disabled';}
                    if($qty == 0 || $product_data->getIsInStock() == 0){$qty = 'Out of Stock';}
                    $product_list[] = array(
                        'id'     => $product->getId(),
                        'sku'    => $product_data->getSku(),
                        'name'   => $product_data->getName(),
                        'status' => $status,
                        'qty'    => $qty,
                        'price'  => strip_tags(Mage::helper('core')->currency(Mage::helper('mobileassistant')->getPriceFormat($product_data->getPrice()))),
                        'type'   => $product->getTypeId()
                    );
                }
                $updated_time       = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));
                $productResultArr  = array('productlistdata' => $product_list,'updated_time' =>$updated_time);
                $productListResult = Mage::helper('core')->jsonEncode($productResultArr);
                return Mage::app()->getResponse()->setBody($productListResult);
            }else{
                $isEnable    = Mage::helper('core')->jsonEncode(array('enable' => false));
                return Mage::app()->getResponse()->setBody($isEnable);
            }
        }

        public function getProductDetailAction()
        {
            if(Mage::helper('mobileassistant')->isEnable()){
                $post_data = Mage::app()->getRequest()->getParams();
                $sessionId = $post_data['session'];
                if (!Mage::getSingleton('api/session')->isLoggedIn($sessionId)) {
                    echo $this->__("The Login has expired. Please try log in again.");
                    return false;
                }
                $storeId      = $post_data['storeid'];
                $productId    = $post_data['productid'];
                $product_data = Mage::getModel('catalog/product')->load($productId);
                $status       = $product_data->getStatus();
                $qty          = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product_data)->getQty();

                if($status == 1){$status = 'Enabled';}else{$status = 'Disabled';}

                if(!$product_data->isSaleable()){$qty = 'Out of Stock';}


                if($product_data->getTypeId() == 'grouped'){
                    $associated_products = $product_data->getTypeInstance(true)->getAssociatedProducts($product_data); 
                }elseif($product_data->getTypeId() == 'configurable'){
                    $associated_products = $product_data->getTypeInstance()->getUsedProducts(); 
                }  elseif($product_data->getTypeId() == 'bundle'){
                    $associated_products = $product_data->getTypeInstance(true)->getSelectionsCollection($product_data->getTypeInstance(true)->getOptionsIds($product_data), $product_data);  
                }
                foreach($associated_products as $associated_product)
                {
                    $status = $associated_product->getStatus();
                    $qty    = Mage::getModel('cataloginventory/stock_item')->loadByProduct($associated_product)->getQty();
                    if($status == 1){$status = 'Enabled';}else{$status = 'Disabled';}
                    if($qty == 0 || $associated_product->getIsInStock() == 0){$qty = 'Out of Stock';}
                    $associated_products_details[] = array(
                        'id'  => $associated_product->getId(),
                        'sku' => $associated_product->getSku()
                    );

                    $associated_products_list[] = array(
                        'id'     => $associated_product->getId(),
                        'sku'    => $associated_product->getSku(),
                        'name'   => $associated_product->getName(),
                        'status' => $status,
                        'qty'    => $qty,
                        'price'  => strip_tags(Mage::helper('core')->currency(Mage::helper('mobileassistant')->getPriceFormat($associated_product->getPrice())))
                    );
                }
                $product_details[] = array(
                    'id'     => $product_data->getId(),
                    'sku'    => $product_data->getSku(),
                    'name'   => $product_data->getName(),
                    'status' => $status,
                    'qty'    => $qty,
                    'price'  => strip_tags(Mage::helper('core')->currency(Mage::helper('mobileassistant')->getPriceFormat($product_data->getPrice()))),
                    'desc'   => $product_data->getDescription(),
                    'type'   => $product_data->getTypeId(),
                    'image'  => Mage::getModel('catalog/product_media_config')->getMediaUrl($product_data->getImage()),
                    'special_price'   => strip_tags(Mage::helper('core')->currency(Mage::helper('mobileassistant')->getPriceFormat($product_data->getSpecialPrice()))),
                    'associated_skus' => $associated_products_details
                );

                $productResultArr    = array('productdata' => $product_details , 'associated_products_list' =>$associated_products_list);
                $productDetailResult = Mage::helper('core')->jsonEncode($productResultArr);
                return Mage::app()->getResponse()->setBody($productDetailResult);
            }else{
                $isEnable    = Mage::helper('core')->jsonEncode(array('enable' => false));
                return Mage::app()->getResponse()->setBody($isEnable);
            }
        }

        public function filterProductAction()
        {
            if(Mage::helper('mobileassistant')->isEnable()){
                $post_data = Mage::app()->getRequest()->getParams();
                $sessionId = $post_data['session'];
                if (!Mage::getSingleton('api/session')->isLoggedIn($sessionId)) {
                    echo $this->__("The Login has expired. Please try log in again.");
                    return false;
                }
                $storeId          = $post_data['storeid'];
                $filter_by_name   = $post_data['filter_by_name'];
                $filter_by_type   = $post_data['product_type'];
                $filter_by_qty    = $post_data['filter_by_qty'];

                $products  = Mage::getModel('catalog/product')->getCollection()->addStoreFilter($storeId)->setOrder('entity_id', 'desc');

                if($filter_by_name != null){
                    $products->addAttributeToFilter(array(
                            array(
                                'attribute' => 'name',
                                'like' => '%'.$filter_by_name.'%'
                            ),
                            array(
                                'attribute' => 'sku',
                                'like' => '%'.$filter_by_name.'%'
                            )
                        ));
                }

                if($filter_by_type != null){
                    $products->addFieldToFilter('type_id',Array('eq'=>$filter_by_type));
                }

                if($filter_by_qty != null){
                    $products->joinField('qty','cataloginventory/stock_item','qty','product_id=entity_id','{{table}}.stock_id=1','left');
                    if($filter_by_qty == 'gteq'){
                        $qty = $post_data['qty'];
                        $products->addFieldToFilter('qty',Array('gteq'=>$qty));
                    }elseif($filter_by_qty == 'lteq'){
                        $qty = $post_data['qty'];
                        $products->addFieldToFilter('qty',Array('lteq'=>$qty));
                    }elseif($filter_by_qty == 'btwn'){
                        $from_qty = $post_data['from_qty'];
                        $to_qty   = $post_data['to_qty'];
                        $products->addFieldToFilter('qty',array('from'=>$from_qty, 'to'=>$to_qty));
                    }
                }

                foreach($products as $product)
                {
                    $product_data = Mage::getModel('catalog/product')->load($product->getId());
                    $status       = $product_data->getStatus();
                    $qty          = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product_data)->getQty();
                    if($status == 1){$status = 'Enabled';}else{$status = 'Disabled';}
                    if($qty == 0 || $product_data->getIsInStock() == 0){$qty = 'Out of Stock';}
                    $product_list[] = array(
                        'id'     => $product->getId(),
                        'sku'    => $product_data->getSku(),
                        'name'   => $product_data->getName(),
                        'status' => $status,
                        'qty'    => $qty,
                        'price'  => strip_tags(Mage::helper('core')->currency(Mage::helper('mobileassistant')->getPriceFormat($product_data->getPrice()))),
                        'type'   => $product->getTypeId()
                    );
                }

                $productListResultArr = array('productlistdata' => $product_list);
                $productListResult    = Mage::helper('core')->jsonEncode($productListResultArr);
                return Mage::app()->getResponse()->setBody($productListResult);
            }else{
                $isEnable    = Mage::helper('core')->jsonEncode(array('enable' => false));
                return Mage::app()->getResponse()->setBody($isEnable);
            }
        }

}