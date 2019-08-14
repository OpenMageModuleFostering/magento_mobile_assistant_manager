<?php

class Biztech_Mobileassistant_Helper_Data extends Mage_Core_Helper_Abstract
{
           
        public function getPriceFormat($price)
        {
            $price = sprintf("%01.2f", $price);
            return $price;
        }
        
        public function isEnable()
        {
            return Mage::getStoreConfig('mobileassistant/mobileassistant_general/enabled');
        }
}