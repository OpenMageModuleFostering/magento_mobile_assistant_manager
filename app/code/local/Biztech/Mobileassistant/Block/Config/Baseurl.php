<?php

class Biztech_Mobileassistant_Block_Config_Baseurl extends Mage_Adminhtml_Block_System_Config_Form_Field {

    protected function _construct() {
        parent::_construct();
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
        $html = '';
        if (strlen($code = Mage::getSingleton('adminhtml/config_data')->getStore())) { // store level
            $storeId = Mage::getModel('core/store')->load($code)->getId();
            $url = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
            $html = '<span style="color:blue;">' . $url . '</span>';
        } elseif (strlen($code = Mage::getSingleton('adminhtml/config_data')->getWebsite())) { // website level
            $website_id = Mage::getModel('core/website')->load($code)->getId();
            $storeId = Mage::app()->getWebsite($website_id)->getDefaultStore()->getId();
            $url = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
            $html = '<span style="color:blue;">' . $url . '</span>';
        } else { // default level
            $defaultId = array();
            $websites = Mage::app()->getWebsites();
            if (count($websites) > 1) {
                foreach ($websites as $website) {
                    $defaultId[] = $website->getDefaultGroup(true)->getDefaultStoreId();
                }
                $i = 1;
                foreach ($defaultId as $storeId) {
                    $url = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
                    $html = $html . 'Website ' . $i . ': <span style="color:blue;">' . $url . '</span> </br>';
                    $i++;
                }
            } else {
                $storeId = Mage::app()->getWebsite(true)->getDefaultGroup()->getDefaultStoreId();
                $url = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
                $html = '<span style="color:blue;">' . $url . '</span>';
            }
        }
        return $html;
    }

}
