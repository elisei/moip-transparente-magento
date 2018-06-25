<?php
class MOIP_Transparente_Block_Standard_Method_Boleto extends Mage_Checkout_Block_Onepage_Success
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

  
    public function getMoipLineCode(){
        $data = $this->getMoipData();
       return $data['line_code'];
    }

    public function getMoipLinkPrint(){
        $data = $this->getMoipData();
       return $data['print_href'];
    }

    public function getExpirationDate(){
        $data = $this->getMoipData();
       return $data['expiration_date'];
    }

    protected function getMoipData(){
        $additional = $this->getOrder()->getPayment()->getAdditionalData();
        return unserialize($additional);
    }

}