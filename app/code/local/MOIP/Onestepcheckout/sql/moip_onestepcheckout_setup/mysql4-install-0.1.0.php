<?php
	$installer = $this;
	$installer->startSetup();
	$tablePrefix = (string) Mage::getConfig()->getTablePrefix();
	$tablePrefix = $tablePrefix."moip_onestepcheckout";
	$installer->run("DROP TABLE IF EXISTS ".$tablePrefix.";
	CREATE TABLE ".$tablePrefix." (
	  `moip_onestepcheckout_date_id` int(11) unsigned NOT NULL auto_increment,
	  `sales_order_id` int(11) unsigned NOT NULL,
	  `moip_customercomment_info` varchar(255) default '',
	  `status` smallint(6) default '0',
	  `created_time` datetime NULL,
	  `update_time` datetime NULL,
	  PRIMARY KEY (`moip_onestepcheckout_date_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

	$installer->endSetup();
	$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
		$setup->addAttribute('order', 'moip_customercomment', array(
		'label' => 'Customer Comment',
		'type' => 'text',
		'input' => 'text',
		'visible' => true,
		'required' => false,
		'position' => 1,
	));

	

	$installer->endSetup();
