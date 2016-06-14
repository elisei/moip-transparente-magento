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
        $pgto             = $this->getMoipPayment();
        $responseMoipJson = $pgto['response_moip'];
        $responseMoip     = $pgto['response_moip'];
        $forma_pagamento  = $order->getPayment()->getMethodInstance()->getCode();
        $orderIdMoip      = $pgto['order_moip'];
        $mage_pay         = $order->getId();
        $model_del        = Mage::getModel('transparente/write');
        $model_del->load($mage_pay, 'mage_pay');
        if ($model_del->getMoipPay()) {
            $url = "sales/order/view/order_id/" . $mage_pay;
            return;
        }
        $email      = $order->getBillingAddress()->getEmail();
        $customerId = $order->getCustomerId();
        $fees       = $responseMoip->amount->fees;
        $moipidPay  = $responseMoip->id;
        if ($forma_pagamento == "moip_boleto") {
            $href                       = $responseMoip->_links->payBoleto->redirectHref;
            $moip_boleto_expirationDate = $responseMoip->fundingInstrument->boleto->expirationDate;
            $moip_boleto_lineCode       = $responseMoip->fundingInstrument->boleto->lineCode;
            $state_onhold               = $this->initState('order_status_holded_boleto');
            $comment                    = "Aguardando confirmação automática de pagamento.";

        } elseif ($forma_pagamento == "moip_tef") {
            $href                       = $responseMoip->_links->payOnlineBankDebitBB->redirectHref;
            $moip_transf_expirationDate = $responseMoip->fundingInstrument->onlineBankDebit->expirationDate;
            $moip_transf_bankName       = $responseMoip->fundingInstrument->onlineBankDebit->bankName;
            $state_onhold               = $this->initState('order_status_holded_tef');
            $comment                    = "Aguardando confirmação automática de pagamento.";
        } elseif ($forma_pagamento == "moip_cc") {
            $status                     = $responseMoip->status;
            $moip_card_installmentCount = $responseMoip->installmentCount;
            $moip_card_brand            = $responseMoip->fundingInstrument->creditCard->brand;
            if($pgto['save_card']){
                $moip_card_id               = $responseMoip->fundingInstrument->creditCard->id;
            } else {
                $moip_card_id               = null;
            }
            $moip_card_first6           = $responseMoip->fundingInstrument->creditCard->first6;
            $moip_card_last4            = $responseMoip->fundingInstrument->creditCard->last4;
            $moip_card_birthdate        = $responseMoip->fundingInstrument->creditCard->holder->birthdate;
            $moip_card_taxDocument      = $responseMoip->fundingInstrument->creditCard->holder->taxDocument->number;
            $moip_card_fullname         = $responseMoip->fundingInstrument->creditCard->holder->fullname;
            $state_onhold               = $this->initState('order_status_holded');
            $comment                    = "Aguardando confirmação automática de pagamento.";
        }
        $model = Mage::getModel('transparente/write');
        if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste")
            $ambiente = "teste";
        else
            $ambiente = "producao";
        $order_moip = str_replace("ORD-", "", $orderIdMoip);
        $model->setMagePay($mage_pay)->setMoipOrder($order_moip)->setCustomerEmail($email)->setCustomerId($customerId)->setFormaPagamento($forma_pagamento)->setMoipAmbiente($ambiente)->setMoipFees($fees)->setMoipPay($moipidPay);
        if ($forma_pagamento == "moip_boleto") {
            $model->setMoipHrefBoleto($href)->setMoipExpirationBoleto($moip_boleto_expirationDate)->setMoipLinecodeBoleto($moip_boleto_lineCode);
            $model->save();
        } elseif ($forma_pagamento == "moip_tef") {
            $model->setMoipHrefTrans($href)->setMoipBankNameTrans($moip_transf_bankName)->setMoipExpirationTrans($moip_transf_expirationDate);
            $model->save();
        } elseif ($forma_pagamento == "moip_cc") {
            $model->setMoipCardInstallment($moip_card_installmentCount)->setMoipCardBrand($moip_card_brand)->setMoipCardId($moip_card_id)->setMoipCardFirst6($moip_card_first6)->setMoipCardLast4($moip_card_last4)->setMoipCardBirthdate($moip_card_birthdate)->setMoipCardTaxdocument($moip_card_taxDocument)->setMoipCardFullname($moip_card_fullname);
            $model->save();
        }
        $order->setState(Mage_Sales_Model_Order::STATE_HOLDED, $state_onhold, $comment, $notified = false, $includeComment = true);
        $order->sendNewOrderEmail();
        $order->setEmailSent(true);
        $order->save();
        #$order->sendOrderUpdateEmail(true, $comment);


        return $this;
    }


    public function initState($value){
        return Mage::getSingleton('transparente/standard')->getConfigData($value);
    }

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
   
    public function getDataMoip()
    {
        return $this->$_MoipData;
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