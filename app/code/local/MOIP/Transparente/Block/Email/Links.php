<?php 
class MOIP_Transparente_Block_Email_links extends Mage_Core_Block_Template
{
   public function getTypePayment()
   {
   	if ($order = $this->getOrder()) {
        $info  = $order->getPayment()->getMethodInstance()->getCode();
        if ($info == "moip_boleto")
            return 'boleto';
        elseif ($info == "moip_tef")
            return 'tef';
        elseif ($info == "moip_cc")
            return 'cartao';
        else
        	return !1;
     }
   }

   public function getState(){
   	if ($order = $this->getOrder()) {
	   	$order = $this->getOrder();
	   	$status = $order->getState();
	   	return $status;
	}
   }

   public function getDataMoip() {
		if ($order = $this->getOrder()) {
			$data_array = array();
			
			
			$readConnection =  Mage::getSingleton('core/resource')->getConnection('core_read');
			$table = (string) Mage::getConfig()->getTablePrefix().'moip_transparentev2';

			$query = 'SELECT * FROM ' . $table .' WHERE mage_pay='.$order->getId().'';
			$results = $readConnection->fetchAll($query);

			if($results){
				return $results[0];
			} else {
				return !1;
			}

		} else {
			return !1;
		}

	}

}
?>