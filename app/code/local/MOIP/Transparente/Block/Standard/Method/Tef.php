<?php
class MOIP_Transparente_Block_Standard_Method_Tef extends Mage_Checkout_Block_Onepage_Success
{


    public function getOrder()
    {
        $final = "";
        $orderId       = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $current_order = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('increment_id', $orderId);
        if ($current_order) {
            foreach ($current_order as $order) {
                $final = $order;
                break;
            }
        }
        return $final;
    }

  
    public function getMoipTransfHref(){
        $data = $this->getMoipData();
       return $data['moip_transf_href'];
    }
        
   
    protected function getMoipData(){
        $additional = $this->getOrder()->getPayment()->getAdditionalData();
        return unserialize($additional);
    }
}