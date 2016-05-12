<?php
class MOIP_Transparente_Block_Standard_Cancel extends Mage_Checkout_Block_Onepage_Failure{


	public function __construct(){
		$this->_expireCart();
		$session = Mage::getSingleton('checkout/session');
		Mage::dispatchEvent('sales_order_item_cancel', array('order_ids' => array($session->getOrder()->getId())));
		parent::__construct();
	}
}
