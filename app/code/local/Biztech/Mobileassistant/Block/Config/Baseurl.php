<?php
    class Biztech_Mobileassistant_Block_Config_Baseurl extends Mage_Adminhtml_Block_System_Config_Form_Field
    {    
        
        protected function _construct()
        {
            parent::_construct();
        }
        protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
        {
            return '<span style="color:blue;">'.Mage::getBaseUrl().'</span>';
        }

    }
