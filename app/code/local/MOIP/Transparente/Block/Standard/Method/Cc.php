<?php
class MOIP_Transparente_Block_Standard_Method_Cc extends Mage_Checkout_Block_Onepage_Success
{

	public function getMoipPayment()
    {
        return $this->getCheckout()->getMoipData();
    }
    public function getCardStatus()
    {
        $pgto             = $this->getMoipPayment();
        $responseMoipJson = $pgto['response_moip'];
        return $responseMoipJson->status;
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