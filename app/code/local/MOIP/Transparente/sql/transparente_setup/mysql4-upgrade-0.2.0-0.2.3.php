<?php
/**
 * Transparente - Transparente Payment Module
 *
 * @title      Magento -> Custom Payment Module for Transparente (Brazil)
 * @category   Payment Gateway
 * @package    MOIP_Transparente
 * @author     Transparente Pagamentos S/a
 * @copyright  Copyright (c) 2013 Moip Soluções Web
 * @license    Licença válida por tempo indeterminado
 */

$installer = $this;
$installer->startSetup();
$installer->run("
		ALTER TABLE  `".$this->getTable('sales/order')."` ADD  `fee_amount_moip` DECIMAL( 10, 2 ) NOT NULL;
		ALTER TABLE  `".$this->getTable('sales/order')."` ADD  `base_fee_amount_moip` DECIMAL( 10, 2 ) NOT NULL;
		ALTER TABLE  `".$this->getTable('sales/quote_address')."` ADD  `fee_amount_moip` DECIMAL( 10, 2 ) NOT NULL;
		ALTER TABLE  `".$this->getTable('sales/quote_address')."` ADD  `base_fee_amount_moip` DECIMAL( 10, 2 ) NOT NULL;
		ALTER TABLE  `".$this->getTable('sales/order')."` ADD  `fee_amount_moip_invoiced` DECIMAL( 10, 2 ) NOT NULL;
		ALTER TABLE  `".$this->getTable('sales/order')."` ADD  `base_fee_amount_moip_invoiced` DECIMAL( 10, 2 ) NOT NULL;
		ALTER TABLE  `".$this->getTable('sales/order')."` ADD  `fee_amount_moip_refunded` DECIMAL( 10, 2 ) NOT NULL;
		ALTER TABLE  `".$this->getTable('sales/order')."` ADD  `base_fee_amount_moip_refunded` DECIMAL( 10, 2 ) NOT NULL;
		ALTER TABLE  `".$this->getTable('sales/invoice')."` ADD  `fee_amount_moip` DECIMAL( 10, 2 ) NOT NULL;
		ALTER TABLE  `".$this->getTable('sales/invoice')."` ADD  `base_fee_amount_moip` DECIMAL( 10, 2 ) NOT NULL;
		ALTER TABLE  `".$this->getTable('sales/creditmemo')."` ADD  `fee_amount_moip` DECIMAL( 10, 2 ) NOT NULL;
		ALTER TABLE  `".$this->getTable('sales/creditmemo')."` ADD  `base_fee_amount_moip` DECIMAL( 10, 2 ) NOT NULL;
	");
$installer->endSetup();
?>
