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

$status = Mage::getModel('sales/order_status');

$status->setStatus('canceled_opportunity_available')->setLabel('Pgto Cancelado mas com oportunidade de CDC')
    ->assignState(Mage_Sales_Model_Order::STATE_CANCELED)
    ->save();
$installer->endSetup();
?>
