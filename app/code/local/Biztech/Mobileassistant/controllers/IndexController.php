<?php

class Biztech_Mobileassistant_IndexController extends Mage_Core_Controller_Front_Action {

    public function indexAction() {
        if (Mage::getStoreConfig('mobileassistant/mobileassistant_general/enabled')) {

            $isSecure = Mage::app()->getFrontController()->getRequest()->isSecure();
            $validate_url = false;
            if ($isSecure) {
                if (Mage::getStoreConfig('web/secure/base_url') == Mage::getStoreConfig('web/secure/base_link_url')) {
                    $validate_url = true;
                }
            } else {
                if (Mage::getStoreConfig('web/unsecure/base_url') == Mage::getStoreConfig('web/unsecure/base_link_url')) {
                    $validate_url = true;
                }
            }

            if ($validate_url) {

                $details = Mage::app()->getRequest()->getParams();

                $username = $details['userapi'];
                $password = $details['keyapi'];
                $deviceToken = $details['token'];
                $flag = $details['notification_flag'];
                $device_type = $details['device_type'];

                Mage::register('isSecureArea', true);

                $config = Mage::getStoreConfigFlag('admin/security/use_case_sensitive_login');
                Mage::getSingleton('core/session', array('name' => 'adminhtml'));
                $user = Mage::getModel('admin/user')->loadByUsername($username);

                $sensitive = ($config) ? $username == $user->getUsername() : true;

                if ($sensitive && $user->getId() && Mage::helper('core')->validateHash($password, $user->getPassword())) {
                    if ($user->getIsActive() != '1') {
                        $result['error'] = Mage::helper('adminhtml')->__('This account is inactive.');
                    }
                    if (!$user->hasAssigned2Role($user->getId())) {
                        $result['error'] = Mage::helper('adminhtml')->__('Access denied.');
                    }
                } else {
                    $result['error'] = $this->__('Invalid User Name or Password.');
                    $jsonData = Mage::helper('core')->jsonEncode($result);
                    return Mage::app()->getResponse()->setBody($jsonData);
                }
                if (Mage::getSingleton('adminhtml/url')->useSecretKey()) {
                    Mage::getSingleton('adminhtml/url')->renewSecretUrls();
                }
                $session = Mage::getSingleton('admin/session');

                $session->setIsFirstVisit(true);
                $session->setUser($user);
                $session->setAcl(Mage::getResourceModel('admin/acl')->loadAcl());
                $session_id = $user->getId() . '_' . md5($username);

                Mage::dispatchEvent('admin_session_user_login_success', array('user' => $user));

                Mage::unregister('isSecureArea');

                if ($session_id) {
                    $data = array('username' => $username, 'password' => $user->getPassword(), 'devicetoken' => $deviceToken, 'session_id' => $session_id, 'notification_flag' => $flag, 'device_type' => $device_type, 'is_logout' => 0);
                    $result = Mage::helper('mobileassistant')->create($data);


                    $jsonData = Mage::helper('core')->jsonEncode($result);
                    return Mage::app()->getResponse()->setBody($jsonData);
                }
            } else {
                $result['error'] = $this->__('There seems some difference between the Based URL & Magento Based URL(on the store). Please check & if issue persists, Contact our Support Team.');
            }
        } else {
            $result['error'] = $this->__('Please activate the Mobile Assistant Extension on the Magento Store.');
        }
        $jsonData = Mage::helper('core')->jsonEncode($result);
        return Mage::app()->getResponse()->setBody($jsonData);
    }

    public function testModuleAction() {
        $post_data = Mage::app()->getRequest()->getParams();
        $url = $post_data['magento_url'];
        $url_info = parse_url($url);

        if (Mage::getConfig()->getModuleConfig('Biztech_Mobileassistant')->is('active', 'true') && Mage::getStoreConfig('mobileassistant/mobileassistant_general/enabled')) {

            $isSecure = Mage::app()->getFrontController()->getRequest()->isSecure();
            $validate_url = false;
            if ($isSecure) {
                if (Mage::getStoreConfig('web/secure/base_url') == Mage::getStoreConfig('web/secure/base_link_url')) {
                    $validate_url = true;
                }

                if ($url_info['scheme'] == 'http') {
                    $result['error'] = $this->__('It seems you use secure url for your store. So please use "https". ');
                    $jsonData = Mage::helper('core')->jsonEncode($result);
                    return Mage::app()->getResponse()->setBody($jsonData);
                }
            } else {
                if (Mage::getStoreConfig('web/unsecure/base_url') == Mage::getStoreConfig('web/unsecure/base_link_url')) {
                    $validate_url = true;
                }
            }
            if ($validate_url) {

                $is_index = Mage::getStoreConfig('web/seo/use_rewrites');
                if (!$is_index && basename($url) != 'index.php') {
                    $result['error'] = $this->__('Please add "index.php" after your url.');
                    $jsonData = Mage::helper('core')->jsonEncode($result);
                    return Mage::app()->getResponse()->setBody($jsonData);
                }

                $result['success'] = $this->__('Hurray! The connection with the Magento Site worked out fine & you can start using the App.');
            } else {
                $result['error'] = $this->__('There seems some difference between the Based URL & Magento Based URL(on the store). Please check & if issue persists, Contact our Support Team.');
            }
        } else {
            $result['error'] = $this->__('Please activate the Mobile Assistant Extension on the Magento Store.');
        }
        $jsonData = Mage::helper('core')->jsonEncode($result);
        return Mage::app()->getResponse()->setBody($jsonData);
    }

    public function changeSettingsAction() {
        $post_data = Mage::app()->getRequest()->getParams();
        $user = $post_data['userapi'];
        $deviceToken = $post_data['token'];
        $flag = $post_data['notification_flag'];
        $collections = Mage::getModel("mobileassistant/mobileassistant")->getCollection()->addFieldToFilter('username', Array('eq' => $user))->addFieldToFilter('device_token', Array('eq' => $deviceToken));
        $count = count($collections);

        foreach ($collections as $user) {
            $user_id = $user->getUserId();
        }
        if ($count == 1) {
            try {
                $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
                $connection->beginTransaction();
                $fields = array();
                $fields['notification_flag'] = $flag;
                $where = $connection->quoteInto('user_id =?', $user_id);
                $prefix = Mage::getConfig()->getTablePrefix();
                $connection->update($prefix . 'mobileassistant', $fields, $where);
                $connection->commit();
            } catch (Exception $e) {
                return $e->getMessage();
            }
            $successArr[] = array('success_msg' => 'Settings updated sucessfully');
            $result = Mage::helper('core')->jsonEncode($successArr);
            return Mage::app()->getResponse()->setBody($result);
        }
    }

    public function getLogoAndCurrencyAction() {
        $post_data = Mage::app()->getRequest()->getParams();
        $storeId = $post_data['storeid'];
        $block = new Mage_Page_Block_Html_Header();
        $logo = $block->getLogoSrc();
        $currency_code = Mage::getModel('core/store')->load($storeId)->getCurrentCurrencyCode();
        $product = Mage::getModel('catalog/product')->getCollection()->addStoreFilter($storeId)->addAttributeToSelect('*')->addAttributeToFilter('status', array('eq' => 1))->getFirstItem();
        $price = $product->getPrice();
        $price = strip_tags(Mage::app()->getLocale()->currency($currency_code)->toCurrency(sprintf("%01.2f", $price)));
        $currency_symbol = Mage::app()->getLocale()->currency($currency_code)->getSymbol();
        $cur_position = strpos($price, $currency_symbol);
        if ($cur_position == 0) {
            $prefix = 1;
        } else {
            $prefix = 0;
        }
        $isPos = 0;
        $resultArr = array('logo' => $logo, 'currency_symbol' => Mage::app()->getLocale()->currency($currency_code)->getSymbol(), 'is_pos' => $isPos, 'is_Mobileassistantpro' => 0, 'prefix' => $prefix);
        $result = Mage::helper('core')->jsonEncode($resultArr);
        return Mage::app()->getResponse()->setBody($result);
    }

    public function logoutAction() {

        $post_data = Mage::app()->getRequest()->getParams();
        $user = $post_data['userapi'];
        $deviceToken = $post_data['token'];
        $collections = Mage::getModel("mobileassistant/mobileassistant")->getCollection()->addFieldToFilter('device_token', Array('eq' => $deviceToken));
        $count = count($collections);

        foreach ($collections as $user) {
            $device_token = $user->getDeviceToken();

            try {
                $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
                $connection->beginTransaction();
                $fields = array();
                $fields['is_logout'] = 1;
                $where = $connection->quoteInto('device_token =?', $device_token);
                $prefix = Mage::getConfig()->getTablePrefix();
                $connection->update($prefix . 'mobileassistant', $fields, $where);
                $connection->commit();
            } catch (Exception $e) {
                return $e->getMessage();
            }
            $successArr[] = array('success_msg' => 'User logout successfully.');
            $result = Mage::helper('core')->jsonEncode($successArr);
            return Mage::app()->getResponse()->setBody($result);
        }
    }

}
