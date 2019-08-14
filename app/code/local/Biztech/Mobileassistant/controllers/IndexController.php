<?php
    class Biztech_Mobileassistant_IndexController extends Mage_Core_Controller_Front_Action
    {
        public function indexAction()
        {
            if(Mage::getStoreConfig('mobileassistant/mobileassistant_general/enabled')){
                $details     = Mage::app()->getRequest()->getParams();
                $user        = $details['userapi']; 
                $api_key     = $details['keyapi']; 
                $deviceToken = $details['token'];
                $flag        = $details['notification_flag'];
                $url         = $details['magento_url'].'/api/soap?wsdl';
                
                try{
                    $soap       = new SoapClient($url);
                    $session_id = $soap->login($user, $api_key);
                }
                catch(Exception $e){
                    echo $e->getMessage();
                    return false;
                }
                if($session_id){
                    $data[]   = array('user' => $user,'key' => $api_key,'devicetoken'=>$deviceToken,'session_id' => $session_id,'notification_flag'=> $flag);
                    $result   = $soap->call($session_id,'mobileassistant.create',$data);
                    $jsonData = Mage::helper('core')->jsonEncode($result);
                    return Mage::app()->getResponse()->setBody($jsonData);
                }
            }else{
                return Mage::app()->getResponse()->setBody("Please enable this feature");
            }
        }

        public function testModuleAction()
        {
            if(Mage::getConfig()->getModuleConfig('Biztech_Mobileassistant')->is('active', 'true') && Mage::getStoreConfig('mobileassistant/mobileassistant_general/enabled'))
            { 
                $result['success'] = $this->__('Module is activated on this url');
            }else{
                $result['error'] = $this->__('Please activate this module on this url.');
            }
            $jsonData = Mage::helper('core')->jsonEncode($result);
            return Mage::app()->getResponse()->setBody($jsonData);
        }

        public function changeSettingsAction()
        {
            $post_data   = Mage::app()->getRequest()->getParams();
            $user        = $post_data['userapi']; 
            $deviceToken = $post_data['token'];
            $flag        = $post_data['notification_flag'];
            $collections = Mage::getModel("mobileassistant/mobileassistant")->getCollection()->addFieldToFilter('username',Array('eq'=>$user))->addFieldToFilter('device_token',Array('eq'=>$deviceToken));
            $count       = count($collections);

            foreach($collections as $user)
            {
                $user_id = $user->getUserId();
            }
            if($count == 1)
            {
                try {
                    $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
                    $connection->beginTransaction();
                    $fields = array();
                    $fields['notification_flag'] = $flag;
                    $where = $connection->quoteInto('user_id =?', $user_id);
                    $connection->update('mobileassistant', $fields, $where);
                    $connection->commit();
                } catch (Exception $e){
                    return $e->getMessage();
                }
                $successArr[] = array('success_msg' => 'Settings update sucessfully') ;
                $result       = Mage::helper('core')->jsonEncode($successArr);
                return Mage::app()->getResponse()->setBody($result);
            }
        }
}