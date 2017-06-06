<?php
class MOIP_Transparente_Block_Standard_Method_Tef extends Mage_Checkout_Block_Onepage_Success
{

	public function getMoipPayment()
    {
        return $this->getCheckout()->getMoipData();
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

    public function getDebitLinks()
    {
        $pgto             = $this->getMoipPayment();
        $responseMoipJson = $pgto['response_moip'];
        if ($pgto['banknumber_moip'] == '001') {
            return $responseMoipJson->_links->payOnlineBankDebitBB->redirectHref;
        } elseif ($pgto['banknumber_moip'] == '237') {
            return $responseMoipJson->_links->payOnlineBankDebitBradesco->redirectHref;
        } elseif ($pgto['banknumber_moip'] == '341') {
            return $responseMoipJson->_links->payOnlineBankDebitItau->redirectHref;
        } else {
            return $responseMoipJson->_links->payOnlineBankDebitBanrisul->redirectHref;
        }
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