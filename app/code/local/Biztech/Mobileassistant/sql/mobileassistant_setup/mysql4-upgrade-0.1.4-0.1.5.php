<?php

    $installer = $this;

    $installer->startSetup();

    $installer->run("


        ALTER TABLE `{$this->getTable('mobileassistant')}` ADD `device_type` VARCHAR( 255 ) NOT NULL DEFAULT '',
        ADD `is_logout` SMALLINT( 11 ) NOT NULL DEFAULT '0';

        ");


    $installer->endSetup(); 