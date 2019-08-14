<?php

class Biztech_Mobileassistant_Model_Mobileassistant extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('mobileassistant/mobileassistant');
    }
}