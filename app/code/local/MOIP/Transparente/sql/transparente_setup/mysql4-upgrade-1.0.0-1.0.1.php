<?php
$installer = $this;
$installer->startSetup();

$installer->run("
		ALTER TABLE  `".$this->getTable('sales/order')."` ADD  `fee_moip` DECIMAL( 10, 2 ) NOT NULL default '0';
		ALTER TABLE  `".$this->getTable('sales/order')."` ADD  `base_fee_moip` DECIMAL( 10, 4 ) NOT NULL default '0';
		ALTER TABLE  `".$this->getTable('sales/order_item')."` ADD  `fee_moip` DECIMAL( 10, 2 ) NOT NULL default '0';
		ALTER TABLE  `".$this->getTable('sales/order_item')."` ADD  `base_fee_moip` DECIMAL( 10, 4 ) NOT NULL default '0';
		ALTER TABLE  `".$this->getTable('sales/invoice')."` ADD  `fee_moip` DECIMAL( 10, 2 ) NOT NULL default '0';
		ALTER TABLE  `".$this->getTable('sales/invoice')."` ADD  `base_fee_moip` DECIMAL( 10, 4 ) NOT NULL default '0';
		ALTER TABLE  `".$this->getTable('sales/creditmemo')."` ADD  `fee_moip` DECIMAL( 10, 2 ) NOT NULL default '0';
		ALTER TABLE  `".$this->getTable('sales/creditmemo')."` ADD  `base_fee_moip` DECIMAL( 10, 4 ) NOT NULL default '0';
	");
$installer->endSetup();
?>
