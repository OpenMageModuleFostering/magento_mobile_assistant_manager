<?php

class Biztech_Mobileassistant_ProductController extends Mage_Core_Controller_Front_Action {

    public function getProductListAction() {
        if (Mage::helper('mobileassistant')->isEnable()) {

            $post_data = Mage::app()->getRequest()->getParams();
            $sessionId = '';
            $offset = '';
            $limit = '';
            $new_products = '';
            $is_refresh = '';
            $last_fetch_product = '';
            $min_fetch_product = '';
            $last_updated = '';

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
            if (isset($post_data['limit'])) {
                $limit = $post_data['limit'];
            }
            if (isset($post_data['offset'])) {
                $offset = $post_data['offset'];
            }
            if (isset($post_data['last_fetch_product'])) {
                $new_products = $post_data['last_fetch_product'];
            }
            if (isset($post_data['is_refresh'])) {
                $is_refresh = $post_data['is_refresh'];
            }


            $products = Mage::getModel('catalog/product')->getCollection()->addStoreFilter($storeId)->setOrder('entity_id', 'desc');
            if ($offset != null) {
                $products->addAttributeToFilter('entity_id', array('lt' => $offset));
            }


            if ($is_refresh == 1) {
                if (isset($post_data['last_fetch_product'])) {
                    $last_fetch_product = $post_data['last_fetch_product'];
                }
                if (isset($post_data['min_fetch_product'])) {
                    $min_fetch_product = $post_data['min_fetch_product'];
                }
                if (isset($post_data['last_updated'])) {
                    $last_updated = $post_data['last_updated'];
                }
                $products->getSelect()->where("(entity_id BETWEEN '" . $min_fetch_product . "'AND '" . $last_fetch_product . "') OR entity_id >'" . $last_fetch_product . "'");
            }

            $products->getSelect()->limit($limit);

            foreach ($products as $product) {
                $product_data = Mage::getModel('catalog/product')->load($product->getId());
                $status = $product_data->getStatus();
                $qty = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product_data)->getQty();
                if ($status == 1) {
                    $status = 'Enabled';
                } else {
                    $status = 'Disabled';
                }
                if ($qty < 0 || $product_data->getIsInStock() == 0) {
                    $qty = 'Out of Stock';
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
            $updated_time = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));
            $productResultArr = array('productlistdata' => $product_list, 'updated_time' => $updated_time);
            $productListResult = Mage::helper('core')->jsonEncode($productResultArr);
            return Mage::app()->getResponse()->setBody($productListResult);
        } else {
            $isEnable = Mage::helper('core')->jsonEncode(array('enable' => false));
            return Mage::app()->getResponse()->setBody($isEnable);
        }
    }

    public function getProductDetailAction() {
        if (Mage::helper('mobileassistant')->isEnable()) {
            $post_data = Mage::app()->getRequest()->getParams();

            $storeId = '';
            $productId = '';
            $productSku = '';
            $associated_products = array();
            $associated_products_list = '';
            $associated_products_details = '';
            $images = '';

            if (isset($post_data['session'])) {
                $sessionId = $post_data['session'];
            }

            if (!$sessionId || $sessionId == NULL) {
                echo $this->__("The Login has expired. Please try log in again.");
                return false;
            }
            try {
                if (isset($post_data['storeid'])) {
                    $storeId = $post_data['storeid'];
                }
                if (isset($post_data['productid'])) {
                    $productId = $post_data['productid'];
                }
                if (isset($post_data['sku'])) {
                    $productSku = $post_data['sku'];
                }
                if (isset($productSku) && $productSku != null) {
                    $product = Mage::getModel('catalog/product')->setStoreId($storeId)->loadByAttribute('sku', $productSku);
                    if ($product) {
                        $product_data = Mage::getModel('catalog/product')->setStoreId($storeId)->load($product->getId());
                    } else {
                        $result = Mage::helper('core')->jsonEncode(array('mesage' => 'No product found.'));
                        return Mage::app()->getResponse()->setBody($result);
                    }
                } else {
                    $product_data = Mage::getModel('catalog/product')->setStoreId($storeId)->load($productId);
                }


                $pro_status = $product_data->getStatus();
                $pro_qty = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product_data)->getQty();

                $_images = $product_data->getMediaGalleryImages();
                if ($_images) {
                    foreach ($_images as $_image) {
                        $images[] = Mage::helper('catalog/image')->init($product_data, 'thumbnail', $_image->getFile())->resize(300, 330)->keepAspectRatio(true)->constrainOnly(true)->__toString();
                    }
                }
                if ($pro_status == 1) {
                    $pro_status = 'Enabled';
                } else {
                    $pro_status = 'Disabled';
                }

                if ($product_data->getTypeId() == 'grouped') {
                    $associated_products = $product_data->getTypeInstance(true)->getAssociatedProducts($product_data);
                } elseif ($product_data->getTypeId() == 'configurable') {
                    $associated_products = $product_data->getTypeInstance()->getUsedProducts();
                } elseif ($product_data->getTypeId() == 'bundle') {
                    $associated_products = $product_data->getTypeInstance(true)->getSelectionsCollection($product_data->getTypeInstance(true)->getOptionsIds($product_data), $product_data);
                }
                foreach ($associated_products as $associated_product) {
                    $status = $associated_product->getStatus();
                    $qty = Mage::getModel('cataloginventory/stock_item')->loadByProduct($associated_product)->getQty();
                    if ($status == 1) {
                        $status = 'Enabled';
                    } else {
                        $status = 'Disabled';
                    }

                    $associated_products_details[] = array(
                        'id' => $associated_product->getId(),
                        'sku' => $associated_product->getSku()
                    );

                    $associated_products_list[] = array(
                        'id' => $associated_product->getId(),
                        'sku' => $associated_product->getSku(),
                        'name' => $associated_product->getName(),
                        'status' => $status,
                        'qty' => $qty,
                        'is_in_stock' => $associated_product->getIsInStock(),
                        'price' => Mage::helper('mobileassistant')->getPrice($associated_product->getPrice(), $storeId, Mage::app()->getStore()->getCurrentCurrencyCode()),
                    );
                }

                $product_details[] = array(
                    'id' => $product_data->getId(),
                    'sku' => $product_data->getSku(),
                    'name' => $product_data->getName(),
                    'status' => $pro_status,
                    'qty' => $pro_qty,
                    'is_in_stock' => $product_data->getIsInStock(),
                    'price' => Mage::helper('mobileassistant')->getPrice($product_data->getPrice(), $storeId, Mage::app()->getStore()->getCurrentCurrencyCode()),
                    'desc' => $product_data->getDescription(),
                    'type' => $product_data->getTypeId(),
                    'special_price' => Mage::helper('mobileassistant')->getPrice($product_data->getSpecialPrice(), $storeId, Mage::app()->getStore()->getCurrentCurrencyCode()),
                    'image' => ($product_data->getImage()) ? Mage::helper('catalog/image')->init($product_data, 'image', $product_data->getImage())->resize(300, 330)->keepAspectRatio(true)->constrainOnly(true)->__toString() : 'N/A',
                    'associated_skus' => $associated_products_details,
                    'all_images' => $images,
                );

                $productResultArr = array('productdata' => $product_details, 'associated_products_list' => $associated_products_list);
                $productDetailResult = Mage::helper('core')->jsonEncode($productResultArr);
                return Mage::app()->getResponse()->setBody($productDetailResult);
            } catch (Exception $e) {
                $product_details = array(
                    'status' => 'error',
                    'message' => $e->getMessage()
                );
                return Mage::app()->getResponse()->setBody(Mage::helper('core')->jsonEncode($product_details));
            }
        } else {
            $isEnable = Mage::helper('core')->jsonEncode(array('enable' => false));
            return Mage::app()->getResponse()->setBody($isEnable);
        }
    }

    public function filterProductAction() {
        if (Mage::helper('mobileassistant')->isEnable()) {
            $post_data = Mage::app()->getRequest()->getParams();

            $sessionId = '';
            $storeId = '';
            $filter_by_name = '';
            $filter_by_type = '';
            $filter_by_qty = '';
            $product_list = '';
            if (isset($post_data['session'])) {
                $sessionId = $post_data['session'];
            }

            if (!$sessionId || $sessionId == NULL) {
                echo $this->__("The Login has expired. Please try log in again.");
                return false;
            }
            try {
                if (isset($post_data['storeid'])) {
                    $storeId = $post_data['storeid'];
                }
                if (isset($post_data['filter_by_name'])) {
                    $filter_by_name = $post_data['filter_by_name'];
                }
                if (isset($post_data['product_type'])) {
                    $filter_by_type = $post_data['product_type'];
                }
                if (isset($post_data['filter_by_qty'])) {
                    $filter_by_qty = $post_data['filter_by_qty'];
                }
                $products = Mage::getModel('catalog/product')->getCollection()->addStoreFilter($storeId)->setOrder('entity_id', 'desc');

                if ($filter_by_name != null) {
                    $products->addAttributeToFilter(array(
                        array(
                            'attribute' => 'name',
                            'like' => '%' . $filter_by_name . '%'
                        ),
                        array(
                            'attribute' => 'sku',
                            'like' => '%' . $filter_by_name . '%'
                        )
                    ));
                }

                if ($filter_by_type != null) {
                    $products->addFieldToFilter('type_id', Array('eq' => $filter_by_type));
                }

                if ($filter_by_qty != null) {
                    $products->joinField('qty', 'cataloginventory/stock_item', 'qty', 'product_id=entity_id', '{{table}}.stock_id=1', 'left');
                    if ($filter_by_qty == 'gteq') {
                        $qty = $post_data['qty'];
                        $products->addFieldToFilter('qty', Array('gteq' => $qty));
                    } elseif ($filter_by_qty == 'lteq') {
                        $qty = $post_data['qty'];
                        $products->addFieldToFilter('qty', Array('lteq' => $qty));
                    } elseif ($filter_by_qty == 'btwn') {
                        $from_qty = $post_data['from_qty'];
                        $to_qty = $post_data['to_qty'];
                        $products->addFieldToFilter('qty', array('from' => $from_qty, 'to' => $to_qty));
                    }
                }

                foreach ($products as $product) {
                    $product_data = Mage::getModel('catalog/product')->load($product->getId());
                    $status = $product_data->getStatus();
                    $qty = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product_data)->getQty();
                    if ($status == 1) {
                        $status = 'Enabled';
                    } else {
                        $status = 'Disabled';
                    }
                    if ($qty < 0 || $product_data->getIsInStock() == 0) {
                        $qty = 'Out of Stock';
                    }
                    $product_list[] = array(
                        'id' => $product->getId(),
                        'sku' => $product_data->getSku(),
                        'name' => $product_data->getName(),
                        'status' => $status,
                        'qty' => $qty,
                        'price' => Mage::helper('mobileassistant')->getPrice($product_data->getPrice(), $storeId, Mage::app()->getStore()->getCurrentCurrencyCode()),
                        'type' => $product->getTypeId(),
                        'image' => ($product_data->getImage()) ? Mage::helper('catalog/image')->init($product, 'image', $product_data->getImage())->resize(300, 330)->keepAspectRatio(true)->constrainOnly(true)->__toString() : 'N/A',
                    );
                }

                $productListResultArr = array('productlistdata' => $product_list);
                $productListResult = Mage::helper('core')->jsonEncode($productListResultArr);
                return Mage::app()->getResponse()->setBody($productListResult);
            } catch (Exception $e) {
                $product_list = array(
                    'status' => 'error',
                    'message' => $e->getMessage()
                );
                return Mage::app()->getResponse()->setBody(Mage::helper('core')->jsonEncode($product_list));
            }
        } else {
            $isEnable = Mage::helper('core')->jsonEncode(array('enable' => false));
            return Mage::app()->getResponse()->setBody($isEnable);
        }
    }

    public function ChangeProductStatusAction() {
        if (Mage::helper('mobileassistant')->isEnable()) {
            $post_data = Mage::app()->getRequest()->getParams();
            $sessionId = $post_data['session'];

            if (!$sessionId || $sessionId == NULL) {
                echo $this->__("The Login has expired. Please try log in again.");
                return false;
            }
            try {
                $storeId = $post_data['storeid'];
                $productId = $post_data['productid'];
                $current_status = $post_data['current_status'];
                $new_status = $post_data['new_status'];


                if ($current_status != $new_status && $new_status == 1) {
                    Mage::getModel('catalog/product_status')->updateProductStatus($productId, $storeId, Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
                } elseif ($current_status != $new_status && $new_status == 2) {
                    Mage::getModel('catalog/product_status')->updateProductStatus($productId, $storeId, Mage_Catalog_Model_Product_Status::STATUS_DISABLED);
                }
                $productResultArr = array('message' => 'Product has been successfully updated.');
                $productDetailResult = Mage::helper('core')->jsonEncode($productResultArr);
                return Mage::app()->getResponse()->setBody($productDetailResult);
            } catch (Exception $e) {
                $product_details = array(
                    'status' => 'error',
                    'message' => $e->getMessage()
                );
                return Mage::app()->getResponse()->setBody(Mage::helper('core')->jsonEncode($product_details));
            }
        } else {
            $isEnable = Mage::helper('core')->jsonEncode(array('enable' => false));
            return Mage::app()->getResponse()->setBody($isEnable);
        }
    }

}
