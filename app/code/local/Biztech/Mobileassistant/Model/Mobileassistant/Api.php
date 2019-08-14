<?php
    class Biztech_Mobileassistant_Model_Mobileassistant_Api extends Mage_Api_Model_Resource_Abstract
    {
        public function create($data)
        {
            $collections = Mage::getModel("mobileassistant/mobileassistant")->getCollection()->addFieldToFilter('username',Array('eq'=>$data['user']))->addFieldToFilter('apikey',Array('eq'=>$data['key']))->addFieldToFilter('device_token',Array('eq'=>$data['devicetoken']));
            $count       = count($collections);
            if($count == 0){ 
                Mage::getModel("mobileassistant/mobileassistant")
                ->setUsername($data['user'])
                ->setApikey($data['key'])
                ->setDeviceToken($data['devicetoken'])
                ->setDeviceType($data['device_type'])
                ->setNotificationFlag($data['notification_flag'])
                ->save();
            }
            if($count == 1){ 
                foreach($collections as $user)
                {
                    $user_id = $user->getUserId();
                    $flag    = $user->getNotificationFlag();
                }
                if($flag != $data['notification_flag'] || $data['is_logout'] != 1){
                    try {
                        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
                        $connection->beginTransaction();
                        $fields = array();
                        $fields['notification_flag'] = $data['notification_flag'];
                        $fields['is_logout'] = $data['is_logout'];
                        $where = $connection->quoteInto('user_id =?', $user_id);
                        $prefix = Mage::getConfig()->getTablePrefix();
                        $connection->update($prefix.'mobileassistant', $fields, $where);
                        $connection->commit();
                    } catch (Exception $e){
                        return $e->getMessage();
                    }
                }
            }

            $successArr[] = array('success_msg' => 'Login sucessfully','session_id' => $data['session_id']) ;

            foreach (Mage::app()->getWebsites() as $website) {
                foreach ($website->getGroups() as $group) {
                    $stores = $group->getStores();
                    foreach ($stores as $store) {
                        $storeArr[] = array('id' =>$store->getId(),
                            'name' => $store->getName()                                
                        );
                    }
                }
            }
            $isPos =  0;
            $result = array('success' => $successArr,'stores' => $storeArr,'is_pos' => $isPos);
            return $result;
        }
    }

