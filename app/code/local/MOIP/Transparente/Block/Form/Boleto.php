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


	public function mostraBoleto() {
		if (strpos(Mage::getSingleton('transparente/standard')->getConfigData('formas_pagamento'), "BoletoBancario") !== false) {
			return true;
		}else {
			return false;
		}
	}
	
	public function getBoletoIcon() {
		if (Mage::getStoreConfig('moipall/config/trocar_icone')) {
			return Mage::getBaseUrl('media') . "moip/alltransparente/". Mage::getStoreConfig('moipall/config/icone_boleto');
		}else {
			return $this->getSkinUrl('MOIP/transparente/imagem/ababoleto.png');
		}
	}
	
	//imagem do boleto
	public function getBoletoImage() {
		if (Mage::getStoreConfig('moipall/config/trocar_bandeira_cartao')) {
			return Mage::getBaseUrl('media') . "moip/alltransparente/". Mage::getStoreConfig('moipall/config/boleto');
		}else {
			return $this->getSkinUrl('MOIP/transparente/imagem/Bradesco.png');
		}
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
