<?php
class MOIP_Transparente_Block_Standard_Moip extends Mage_Checkout_Block_Onepage_Success
{
    public function __construct()
    {
        
        $this->getSaveDate();
        parent::__construct();
        return $this;
    }

    public function getSaveDate()
    {
        $order            = $this->getOrder();
        
        $order->sendNewOrderEmail();
        $order->setEmailSent(true);
        $order->save();
        
    }

    public function getApi()
    {
        $api = Mage::getModel('transparente/api');
        return $api;
    }

    public function initState($value){
        return Mage::getSingleton('transparente/standard')->getConfigData($value);
    }

    public function getMoipPayment()
    {
        return $this->getCheckout()->getMoipData();
    }
    
    protected function _expireCart()
    {
        if (!Mage::getSingleton('checkout/session')->getLastRealOrderId()) {
            Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('customer/account'));
            exit;
        }
    }

    public function getUrlAmbiente()
    {
        if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste")
            $url = "https://desenvolvedor.moip.com.br/sandbox/";
        else
            $url = "https://www.moip.com.br/";
        return $url;
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

    public function getChildTemplate()
    {
        $order = $this->getOrder();
        $info  = $order->getPayment()->getMethodInstance()->getCode();
        if ($info == "moip_boleto")
            return $this->getChildHtml('transparente.boleto');
        elseif ($info == "moip_tef")
            return $this->getChildHtml('transparente.transferencia');
        elseif ($info == "moip_cc")
            return $this->getChildHtml('transparente.cartao');
    }

    protected function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }
}