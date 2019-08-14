<?php

class Biztech_Mobileassistant_Model_Mysql4_Mobileassistant extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        // Note that the mobileassistant_id refers to the key field in your database table.
        $this->_init('mobileassistant/mobileassistant', 'mobileassistant_id');
    }
}