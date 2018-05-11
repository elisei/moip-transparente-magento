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
 */

class MOIP_Transparente_Block_Form_Boleto extends Mage_Payment_Block_Form {

	protected function _construct() {
		$this->setTemplate('MOIP/transparente/form/boleto.phtml');
		parent::_construct();
	}



	public function getCheckout() {
		return Mage::getSingleton('checkout/session');
	}

	public function getGrandTotal(){
		#var_dump(Mage::getSingleton('checkout/session')->getQuote()->getData());
		return Mage::helper('core')->currency(Mage::getSingleton('checkout/session')->getQuote()->getData('grand_total'), true, false);
	}
	public function getQuote() {
		return $this->getCheckout()->getQuote();
	}


	public function getOnepage() {
		return (string)Mage::getSingleton('checkout/type_onepage');
	}


}
