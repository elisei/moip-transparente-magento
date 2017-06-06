<?php
/**
 * Transparente - Transparente Payment Module
 *
 * @title      Magento -> Custom Payment Module for Transparente (Brazil)
 * @category   Payment Gateway
 * @package    MOIP_Transparente
 * @author     Transparente Pagamentos S/a
 * @copyright  Copyright (c) 2010 Transparente Pagamentos S/A
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @return     Criação dos estados brasileiros
 */
$installer = $this;

$installer->startSetup();

$directory_country_region = Mage::getSingleton('core/resource')->getTableName('directory_country_region');
$directory_country_region_name = Mage::getSingleton('core/resource')->getTableName('directory_country_region_name');
$collection = Mage::getModel('directory/region')->getResourceCollection()->addCountryCodeFilter('BR')->load();

if (count($collection) == 0) {

}



$installer->endSetup();
