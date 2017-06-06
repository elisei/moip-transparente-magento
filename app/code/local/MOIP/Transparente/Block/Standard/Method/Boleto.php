<?php
class MOIP_Transparente_Block_Standard_Method_Boleto extends Mage_Checkout_Block_Onepage_Success
{

	public function getMoipPayment()
    {
        return $this->getCheckout()->getMoipData();
    }
    public function getBoletoLinks()
    {
        $pgto             = $this->getMoipPayment();
        $responseMoipJson = $pgto['response_moip'];
        return $responseMoipJson->_links->payBoleto->redirectHref;
    }
    public function getBoletoCode()
    {
        $pgto             = $this->getMoipPayment();
        $responseMoipJson = $pgto['response_moip'];
        return $responseMoipJson->fundingInstrument->boleto->lineCode;
    }

   
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

    public function getLinkReorder()
    {
          $order            = $this->getOrder();
          return $order->getId();
    }
    protected function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

}