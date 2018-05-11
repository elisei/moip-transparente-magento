<?php
class MOIP_Transparente_Block_Oneclickbuy_MoipOneclickbuy extends Mage_Core_Block_Template
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

	public function getAvailability() {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
			$customerData = Mage::getSingleton('customer/session')->getCustomer();
			$ambiente = Mage::getSingleton('transparente/standard')->getConfigData('ambiente');
			$model = Mage::getModel('transparente/transparente');
			$collection = $model->getCollection()
							->addFieldToSelect(array('moip_card_id','moip_card_brand','moip_card_first6','moip_card_last4','moip_card_fullname'))
							->addFieldToFilter('customer_id', array('eq' => $customerData->getId()))
							->addFieldToFilter('moip_ambiente', array('eq' => $ambiente))
							->addFieldToFilter('moip_card_id', array('neq' => 'NULL'));
			$collection->getSelect()->group('moip_card_id');
			if($collection->getSize() >= 1){
				 return 1;
			} else {
				Mage::getSingleton("core/session")->addNotice("Você ainda não tem um cartão salvo, por favor realize sua compra e cadastre seu cartão");
				return !1;
			}

		} else {
			return 'false';
		}
    }
	

}