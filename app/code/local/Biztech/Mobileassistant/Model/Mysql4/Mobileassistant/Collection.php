<?php

class Biztech_Mobileassistant_Model_Mysql4_Mobileassistant_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('mobileassistant/mobileassistant');
    }
}