<?php
$installer = $this;
$tablePrefix = (string) Mage::getConfig()->getTablePrefix();
$installer->startSetup();
$table_moip = $tablePrefix."moip_transparentev2"; 
$installer->run("CREATE TABLE IF NOT EXISTS `".$table_moip."` (
  `entity_id_moip` int(11) NOT NULL AUTO_INCREMENT,
  `mage_pay` int(11) DEFAULT NULL,
  `moip_order` varchar(256) DEFAULT NULL,
  `customer_email` varchar(256) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `forma_pagamento` varchar(256) DEFAULT NULL,
  `moip_response` longtext DEFAULT NULL,
  `moip_ambiente` varchar(256) DEFAULT NULL,
  `moip_fees` varchar(256) DEFAULT NULL,
  `moip_pay` varchar(256) DEFAULT NULL,
  `moip_href_boleto` varchar(2083) DEFAULT NULL,
  `moip_expiration_boleto` varchar(256) DEFAULT NULL,
  `moip_linecode_boleto` varchar(2083) DEFAULT NULL,
  `moip_href_trans` varchar(2083) DEFAULT NULL,
  `moip_bankName_trans` varchar(256) DEFAULT NULL,
  `moip_expiration_trans` varchar(256) DEFAULT NULL,
  `moip_card_installment` int(1) DEFAULT NULL,
  `moip_card_brand` varchar(256) DEFAULT NULL,
  `moip_card_id` varchar(256) DEFAULT NULL,
  `moip_card_first6` varchar(256) DEFAULT NULL,
  `moip_card_last4` varchar(256) DEFAULT NULL,
  `moip_card_birthdate` varchar(256) DEFAULT NULL,
  `moip_card_taxdocument` varchar(256) DEFAULT NULL,
  `moip_card_fullname` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`entity_id_moip`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");
$installer->endSetup();
?>