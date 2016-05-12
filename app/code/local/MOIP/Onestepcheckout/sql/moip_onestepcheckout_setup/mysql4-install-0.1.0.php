<?php
	$installer = $this;
	$installer->startSetup();
	$tablePrefix = (string) Mage::getConfig()->getTablePrefix();
	$installer->run("DROP TABLE IF EXISTS ".$tablePrefix."moip_onestepcheckout;
	CREATE TABLE ".$tablePrefix."moip_onestepcheckout (
	  `moip_onestepcheckout_date_id` int(11) unsigned NOT NULL auto_increment,
	  `sales_order_id` int(11) unsigned NOT NULL,
	  `moip_customercomment_info` varchar(255) default '',
	  `moip_deliverydate_date` varchar(15) default '',
	  `moip_deliverydate_time` varchar(10) default '',
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

	

	$installer = $this;
	$installer->startSetup();

	$setup = Mage::getModel('customer/entity_setup', 'core_setup');

	$attributes = array(
		array(
			'entity'              => 'customer',
			'code'				  => 'tipopessoa',
			'type'                => 'int',
			'input'  	          => 'boolean',
			'label'               => 'Comprar Como Pessoa Física?',
			'global'              => 1,
			'visible'             => 1,
			'required'            => 0,
			'user_defined'        => 1,
			'default'             => 1,
			'visible_on_front'    => 1,
			'source'              => '',
		),
		array(
			'entity'              => 'customer',
			'code'				=> 'razaosocial',
			'type'                => 'varchar',
			'input'               => 'text',
			'label'               => 'Razao Social',
			'global'              => 1,
			'visible'             => 1,
			'required'            => 0,
			'user_defined'        => 1,
			'default'             => '0',
			'visible_on_front'    => 1,
			'source'              => '',
		),
		array(
			'entity'              => 'customer',
			'code'				  => 'nomefantasia',
			'type'                => 'varchar',
			'input'               => 'text',
			'label'               => 'Nome Fantasia',
			'global'              => 1,
			'visible'             => 1,
			'required'            => 0,
			'user_defined'        => 1,
			'default'             => '0',
			'visible_on_front'    => 1,
			'source'              => '',
		),
		array(
			'entity'              => 'customer',
			'code'				  => 'cnpj',
			'type'                => 'varchar',
			'input'               => 'text',
			'label'               => 'CNPJ',
			'global'              => 1,
			'visible'             => 1,
			'required'            => 0,
			'user_defined'        => 1,
			'default'             => '0',
			'visible_on_front'    => 1,
			'source'              => '',
		),
		array(
			'entity'              => 'customer',
			'code'				  => 'insestadual',
			'type'                => 'varchar',
			'input'               => 'text',
			'label'               => 'Inscrição Estadual',
			'global'              => 1,
			'visible'             => 1,
			'required'            => 0,
			'user_defined'        => 1,
			'default'             => '0',
			'visible_on_front'    => 1,
			'source'              => '',
		)

	);

	foreach($attributes as $attribute){
		$setup->addAttribute($attribute['entity'], $attribute['code'], array(
			'input'               => $attribute['input'],
			'type'                => $attribute['type'],
			'label'               => $attribute['label'],
			'visible'             => $attribute['visible'],
			'required'            => $attribute['required'],
			'global'              => $attribute['global'],
			'default'             => $attribute['default'],
			'visible_on_front'    => $attribute['visible_on_front'],
			'source'              => $attribute['source'],
			'user_defined'        => $attribute['user_defined']
		));

		if (version_compare(Mage::getVersion(), '1.4.2', '>='))
		{
			Mage::getSingleton('eav/config')
			->getAttribute($attribute['entity'], $attribute['code'])
			->setData('used_in_forms', array('adminhtml_customer','customer_account_create','customer_account_edit','checkout_register'))
			->save();
		}else{
			$tablequote = $this->getTable('sales/quote');
			$installer->run("ALTER TABLE  {$tablequote} ADD  `{$attribute['code']}` INT NOT NULL");
		}
	}

	$installer->endSetup();
