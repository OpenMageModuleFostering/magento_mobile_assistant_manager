<?php
    class Biztech_Mobileassistant_Block_Config_Barcodeurl extends Mage_Adminhtml_Block_System_Config_Form_Field
    {    
        
        protected function _construct()
        {
            parent::_construct();
            $this->setTemplate('mobileassistant/system/config/barcode.phtml');
        }
        protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
        {
            return $this->_toHtml();
        }

    }
