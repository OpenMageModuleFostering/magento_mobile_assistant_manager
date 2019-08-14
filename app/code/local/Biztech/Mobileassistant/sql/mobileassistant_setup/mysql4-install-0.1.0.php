<?php

    $installer = $this;

    $installer->startSetup();

    $installer->run("

        -- DROP TABLE IF EXISTS {$this->getTable('mobileassistant')};
        CREATE TABLE {$this->getTable('mobileassistant')} (
        `user_id` int(11) unsigned NOT NULL auto_increment,
        `username` varchar(255) NOT NULL default '',
        `apikey` varchar(40) NOT NULL default '',
        `device_token` varchar(255) NOT NULL default '',
        `notification_flag` smallint(11) NOT NULL default '1',
        PRIMARY KEY (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        ");

    $installer->endSetup(); 