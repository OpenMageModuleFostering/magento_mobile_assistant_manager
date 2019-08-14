<?php

class Biztech_Mobileassistant_Block_Adminhtml_Enabledisable extends Mage_Adminhtml_Block_System_Config_Form_Field {

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
        $websites = Mage::helper('mobileassistant')->getAllWebsites();
        if (!empty($websites)) {
            $website_id = $this->getRequest()->getParam('website');
            $website = Mage::getModel('core/website')->load($website_id, 'code');
            if ($website && in_array($website->getWebsiteId(), $websites)) {
                $html = $element->getElementHtml();
            } elseif (!$website_id) {
                $html = $element->getElementHtml();
            } else {
                $html = '<strong class="required">' . $this->__('Please buy additional domains') . '</strong>';
            }
        } else {
            $websitecode = Mage::app()->getRequest()->getParam('website');
            $websiteId = Mage::getModel('core/store')->load($websitecode)->getWebsiteId;
            $isenabled = Mage::app()->getWebsite($websiteId)->getConfig('mobileassistant/activation/key');
            if ($isenabled != null || $isenabled != '') {
                $html = '<strong class="required">' . $this->__(' Please select a website') . '</strong>';
                $modulestatus = new Mage_Core_Model_Config();
                $modulestatus->saveConfig('mobileassistant/mobileassistant_general/enabled', 0);
            } else {
                $html = '<strong class="required">' . $this->__('Please enter a valid key') . '</strong>';
            }
        }
        return $html;
    }

}
