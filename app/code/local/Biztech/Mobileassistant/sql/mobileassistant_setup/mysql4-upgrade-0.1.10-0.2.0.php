<?php

    $installer = $this;

    $installer->startSetup();

    $installer->run("

        ALTER TABLE `{$this->getTable('mobileassistant')}` ADD `password` VARCHAR( 255 ) NOT NULL DEFAULT '';

        "); 

    $installer->endSetup(); 