<?php
$installer = $this;
$tablePrefix = (string) Mage::getConfig()->getTablePrefix();
$installer->startSetup();
$table_moip = $tablePrefix."moip_transparentev2"; 
$installer->run("CREATE TABLE IF NOT EXISTS `".$table_moip."` (
  `entity_id_moip` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `moip_ambiente` varchar(256) DEFAULT NULL,
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