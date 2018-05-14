<?php
class MOIP_Transparente_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
    const METHOD_CODE = 'moip_transparente_standard';
    protected $_code = self::METHOD_CODE;
    protected $_formBlockType = 'transparente/form_admin';
    protected $_infoBlockType = 'transparente/info_admin';
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = false;
    protected $_canUseForMultishipping = false;
    protected $_canSaveCc = true;
    protected $_allowCurrencyCode = array('BRL');
    protected $_canFetchTransactionInfo = false;
    protected $_isInitializeNeeded = false;

    public function assignData($data)
    {
        $additionaldata = array();
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        return $this;
    }
    
    public function order(Varien_Object $payment, $amount){
       
       
        $order             =  $payment->getOrder();
        $json_order        = $this->getApi()->getDados($order);
        $moip              = $this->getApi()->getOrderMoip($json_order);

            $this->paymentCapture($moip, $payment);
        return $this;

    }
    public function paymentCapture($moip, $payment){
        $order  =    $payment->getOrder();
        $order_mage = Mage::getModel('sales/order')->load($order->getIncrementId(), 'increment_id');
        $order_moip = $moip->id;
       
        if($order_mage){
            
            $mage_pay         = $order_mage->getId();
            $forma_pagamento  = $payment->getMethodInstance()->getCode();
            
           

            if ($forma_pagamento == "moip_transparente_standard") {
               
                $orderIdMoip      = $moip->id;
                $email            = $order->getCustomerEmail();
                $customerId       = $order->getCustomerId();

                $responseMoip     = $moip;
                $fees             = $moip->amount->fees;
                $moipidPay        = $moip->id;
                
                
                 
            } else {
                return $this; 
            }

            if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste"){
                $ambiente = "teste";
            }
            else{
                $ambiente = "producao";
            }


            $model = Mage::getModel('transparente/transparente');
            $order_moip = str_replace("ORD-", "", $order_moip);
            $model->setMagePay($mage_pay)->setMoipOrder($order_moip)->setCustomerEmail($email)->setCustomerId($customerId)->setFormaPagamento($forma_pagamento)->setMoipAmbiente($ambiente)->setMoipFees($fees)->setMoipPay($mage_pay);
            
            $model->save();

            $link_boleto = $moip->_links->checkout->payBoleto->redirectHref;
            $link_cc = $moip->_links->checkout->payCreditCard->redirectHref;
            $info           = $payment->getMethodInstance()->getInfoInstance();
            $additionaldata = array(
                'link_boleto' => $link_boleto,
                'link_cc' => $link_cc
            );
            $info->setAdditionalData(serialize($additionaldata))->save()->setAdditionalInformation(serialize($additionaldata))->save();

            return $this;

        }
    }

    public function validate()
    {
        parent::validate();
        $info          = $this->getInfoInstance();
        $currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();
        $errorMsg      = false;
        if ($errorMsg === false) {
            if (!in_array($currency_code, $this->_allowCurrencyCode)) {
                Mage::throwException(Mage::helper('transparente')->__('O Moip Não pode Transacionar pedidos feitos em  (' . $currency_code . ') verifique as configurações de Moeda do seu magento.'));
            }
        }
        if ($errorMsg) {
            Mage::throwException($errorMsg);
        }
        return $this;
    }
    public function getApi()
    {
        $api = Mage::getModel('transparente/admin');
        return $api;
    }
    
}