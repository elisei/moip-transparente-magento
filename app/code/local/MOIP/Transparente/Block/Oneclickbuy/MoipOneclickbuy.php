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
        
            $data_array = array();
            $customerData = Mage::getSingleton( 'customer/session' )->getCustomer();
            $resource = Mage::getSingleton('core/resource');

            $readConnection = $resource->getConnection('core_read');
            $table = Mage::getConfig()->getTablePrefix().'moip_transparentev2';

            $query = 'SELECT * FROM ' . $table .' WHERE customer_id='.$customerData->getID().' AND moip_card_id IS NOT NULL';
            $results = $readConnection->fetchAll($query);

            if($results){
                return 1;
            } else {
            	Mage::getSingleton("core/session")->addNotice("Você ainda não tem um cartão salvo, por favor realize sua compra e cadastre seu cartão");

                return !1;
            }

        

    }
	

}