<?php
class MOIP_Transparente_Block_Onclick_MoipOnclick extends Mage_Core_Block_Template
{


	public function getCheckout() {
		return Mage::getSingleton('checkout/session');
	}
	public function getQuote() {
		return Mage::getSingleton('checkout/session')->getQuote();
	}
	public function getOnepage() {
		return Mage::getSingleton('checkout/type_onepage');
	}
	protected function _getQuote() {
		return Mage::getSingleton('checkout/cart')->getQuote();
	}


	public function _prepareLayout()
	{
		
		
		  return parent::_prepareLayout();
	}
	public function __construct(){

		parent::__construct();
	}


	

}