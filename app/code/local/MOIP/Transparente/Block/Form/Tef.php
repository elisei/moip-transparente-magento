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

class MOIP_Transparente_Block_Form_Tef extends Mage_Payment_Block_Form {

	protected function _construct() {
		$this->setTemplate('MOIP/transparente/form/tef.phtml');
		parent::_construct();
	}


	
	public function mostraTransferencia() {
		if (strpos(Mage::getSingleton('transparente/standard')->getConfigData('formas_pagamento'), "DebitoBancario") !== false) {
			return true;
		}else {
			return false;
		}
	}
	
	public function getTransferenciaIcon() {
		if (Mage::getStoreConfig('moipall/config/trocar_icone')) {
			return Mage::getBaseUrl('media') . "moip/alltransparente/". Mage::getStoreConfig('moipall/config/icone_transf');
		}else {
			return $this->getSkinUrl('MOIP/transparente/imagem/abatransf.png');
		}
	}
	
	//imagens de transferencia
	public function getBBImage() {
		if (Mage::getStoreConfig('moipall/config/trocar_bandeira_cartao')) {
			return Mage::getBaseUrl('media') . "moip/alltransparente/". Mage::getStoreConfig('moipall/config/cartao_bb');
		}else {
			return $this->getSkinUrl('MOIP/transparente/imagem/BancoDoBrasil.png');
		}
	}
	public function getBradescoImage() {
		if (Mage::getStoreConfig('moipall/config/trocar_bandeira_cartao')) {
			return Mage::getBaseUrl('media') . "moip/alltransparente/". Mage::getStoreConfig('moipall/config/cartao_bradesco');
		}else {
			return $this->getSkinUrl('MOIP/transparente/imagem/Bradesco.png');
		}
	}
	public function getItauImage() {
		if (Mage::getStoreConfig('moipall/config/trocar_bandeira_cartao')) {
			return Mage::getBaseUrl('media') . "moip/alltransparente/". Mage::getStoreConfig('moipall/config/cartao_itau');
		}else {
			return $this->getSkinUrl('MOIP/transparente/imagem/Itau.png');
		}
	}
	public function getBanrisulImage() {
		if (Mage::getStoreConfig('moipall/config/trocar_bandeira_cartao')) {
			return Mage::getBaseUrl('media') . "moip/alltransparente/". Mage::getStoreConfig('moipall/config/cartao_banrisul');
		}else {
			return $this->getSkinUrl('MOIP/transparente/imagem/Banrisul.png');
		}
	}


	public function getCheckout() {
		return Mage::getSingleton('checkout/session');
	}


	public function getQuote() {
		return $this->getCheckout()->getQuote();
	}


	public function getOnepage() {
		return (string)Mage::getSingleton('checkout/type_onepage');
	}


}
