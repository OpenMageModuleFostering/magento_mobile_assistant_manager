<?php

class Biztech_Mobileassistant_Model_System_Config_Notification {

    public function toOptionArray() {
        $optionArray = array(
            array(
                'value' => 'order_notification',
                'label' => 'Order Notification',
            ),
            array(
                'value' => 'product_notification',
                'label' => 'Product Notification',
            ),
            array(
                'value' => 'customer_notification',
                'label' => 'Customer Notification',
            ));

        return $optionArray;
    }

}
