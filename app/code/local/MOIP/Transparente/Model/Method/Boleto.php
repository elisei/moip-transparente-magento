<?php
class MOIP_Transparente_Model_Method_Boleto extends Mage_Payment_Model_Method_Abstract

{
    const METHOD_CODE = 'moip_boleto';
    const MOIP_AUTHORIZED = 'AUTHORIZED';
    const MOIP_PRE_AUTHORIZED = 'PRE_AUTHORIZED';
    const MOIP_CANCELLED = 'CANCELLED';
    protected $_code = self::METHOD_CODE;
    protected $_formBlockType = 'transparente/form_boleto';
    protected $_infoBlockType = 'transparente/info_boleto';
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canFetchTransactionInfo = true;
    protected $_isInitializeNeeded = true;
    protected $_canSaveCc = false;
    protected $_allowCurrencyCode = array('BRL');
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_canManageRecurringProfiles = false;
    protected $_canCancelInvoice        = false;

    public function getPayment()
    {
        return $this->getQuote()->getPayment();
    }

    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    public function prepareSave()
    {
        $info = $this->getInfoInstance();
        return $this;
    }

    public function prepare()
    {
        $info = $this->getInfoInstance();
        $additionaldata = unserialize($info->getAdditionalData());
      return $this;
    }

    public function getApi()
    {
        $api = Mage::getModel('transparente/api');
        return $api;
    }
    
    public function setStore($store)
    {
        $this->setData('store', $store);
        if (null === $store) {
            $store = Mage::app()->getStore()->getId();
        }
        return $this;
    }

    public function cancel(Varien_Object $payment)
    {
        return $this->_void($payment);
    }
    
    public function void(Varien_Object $payment)
    {
        return $this->_void($payment);
    }

    public function authorize(Varien_Object $payment, $amount)
    {
        $this->_authorize($payment, $amount, false);
        return $this;
    }

    
    public function canInvoice()
    {
        $payment = $this->getInfoInstance();
        if ($payment) {
            $transactionAuth = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER);
            
            if (!$transactionAuth || $transactionAuth->getIsClosed()) {
                return false;
            }
        }
        return parent::canInvoice();
    }
    
    public function canCapture()
    {
        $payment = $this->getInfoInstance();
        if ($payment) {
            $transactionAuth = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER);
            
           
            if (!$transactionAuth || $transactionAuth->getIsClosed()) {
                return false;
            }
        }
        return parent::canCapture();
    }

    public function initialize($paymentAction, $stateObject)
    {
        if ($payment = $this->getInfoInstance()) {
            $order = $payment->getOrder();
            $this->setStore($order->getStoreId())->order($payment, $order->getBaseTotalDue());
        }
        if ($this->getConfigData('payment_action') == self::ACTION_AUTHORIZE_CAPTURE) {
            $stateObject->setState(Mage_Sales_Model_Order::STATE_PROCESSING);
        }
        $stateObject->setStatus($this->getConfigData('order_status'));
        $stateObject->setIsNotified(Mage_Sales_Model_Order_Status_History::CUSTOMER_NOTIFICATION_NOT_APPLICABLE);
    }

    public function refund(Varien_Object $payment, $amount)
    {
        if (!$this->canRefund()) {
            Mage::throwException('Unable to refund.');
        }
        $order = $payment->getOrder();
        $transactionAuth = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER);
        $authReferenceId = $transactionAuth->getTxnId();
       
        if (is_null($authReferenceId))
        {
            Mage::throwException(Mage::helper('transparente')->__('Pedido %s não identificado.', $order->getIncrementId()));
        }
        else
        {
            $refund = $this->getApi()->getRefundMoip($authReferenceId, $amount);
            if (isset($consult['error']))
            {
                Mage::throwException(Mage::helper('transparente')->__('Ocorreu um erro ao consultar pedido %s: Msg de erro: %s', $increment_id, $consult['error']));
            } else{
                $payment->setTransactionId($authReferenceId.'-refund');
                $payment->setParentTransactionId($payment->getRefundTransactionId());
                $message = Mage::helper('payment')->__('Solicitado reembolso no valor de %s.', $order->getStore()->convertPrice($amount, true, false));
                $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND, null, false, $message);

            }
        }
        return $this;
    }

    public function capture(Varien_Object $payment, $amount)
    {
        $transactionAuth = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER);
        $authReferenceId = $transactionAuth->getTxnId();
        $details = $transactionAuth->getAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS);
        $moip_pay = $details['moip_pay_id'];
        if ($moip_pay) {
            $consult = $this->getApi()->getMoipPayment($moip_pay);
            if (isset($consult['error']))
            {
                Mage::throwException(Mage::helper('transparente')->__('Ocorreu um erro ao capturar o pedido %s: Msg do erro: %s', $increment_id, $consult['error']));
            } elseif ($consult['status'] != self::MOIP_AUTHORIZED) {
                    Mage::throwException(Mage::helper('transparente')->__('O Pedido %s não pode ser capturado pois ainda não foi autorizado na sua conta Moip, por favor aguarde.', $increment_id));
            } elseif ($consult['status'] == self::MOIP_PRE_AUTHORIZED) { 
                $capture = $this->getApi()->setMoipCapture($moip_pay);
                    if (isset($capture['error'])) {
                        Mage::throwException(Mage::helper('transparente')->__('Ocorreu um erro ao capturar o pedido %s: Msg do erro: %s', $increment_id, $capture['error']));
                    } else {
                       return $this; 
                    }
            } else {
                return $this;
            }

        } else {
            Mage::throwException(Mage::helper('transparente')->__('Pagamento não encontrado.'));
        }
        return $this;
    }


    public function _void(Varien_Object $payment)
    {
        $transactionAuth = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER);
        $orderTransactionId = $transactionAuth->getTxnId();
        $details = $transactionAuth->getAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS);
        $moip_pay = $details['moip_pay_id'];
        if ($moip_pay) {
            return $this;
        } else {
            Mage::throwException(Mage::helper('transparente')->__('Ocorreu um erro ao cancelar esse pedido.'));
        }
        return $this;
    }

    public function _authorize(Varien_Object $payment, $amount, $captureNow = false)
    {
        $order = $payment->getOrder();
        $transactionAuth = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER);
        $authReferenceId = $transactionAuth->getTxnId();

        $details = $transactionAuth->getAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS);
        $moip_pay = $details['moip_pay_id'];

        $consult = $this->getApi()->getMoipPayment($moip_pay);
            if (!isset($consult['error']))
            {
                if ($consult['status'] != self::MOIP_AUTHORIZED)
                {
                    Mage::throwException(Mage::helper('transparente')->__('O Pedido %s não pode ser capturado pois ainda não foi autorizado na sua conta Moip, por favor aguarde.', $increment_id));
                }
                elseif ($consult['status'] == self::MOIP_PRE_AUTHORIZED)
                {
                    $capture = $this->getApi()->setMoipCapture($moip_pay);
                    if (isset($capture['error']))
                    {
                        Mage::throwException(Mage::helper('transparente')->__('Ocorreu um erro ao capturar o pedido %s: Msg do erro: %s', $increment_id, $capture['error']));
                    } else {
                        $payment->setTransactionId($authReferenceId.'-capture');
                        $payment->setParentTransactionId($authReferenceId);
                        $payment->setIsTransactionClosed(false);
                        $invoice = $order->prepareInvoice();
                        if ($this->canCapture())
                        {
                                $invoice->register()->capture();
                        }
                        Mage::getModel('core/resource_transaction')->addObject($invoice)->addObject($invoice->getOrder())->save();
                        $invoice->sendEmail();
                        $invoice->setEmailSent(true);
                        $invoice->save();

                        $transactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE;
                        $message = Mage::helper('payment')->__('Pagamento de %s recebido.', $order->getStore()->convertPrice($amount, true, false));
                        $order->setIsInProcess(true)->save();
                        $payment->addTransaction($transactionType, null, false, $message);
                    }
                } else {
                    $payment->setTransactionId($authReferenceId.'-capture');
                    $payment->setParentTransactionId($authReferenceId);
                    $payment->setIsTransactionClosed(false);
                    $invoice = $order->prepareInvoice();
                    if ($this->canCapture())
                    {
                            $invoice->register()->capture();
                    }
                    Mage::getModel('core/resource_transaction')->addObject($invoice)->addObject($invoice->getOrder())->save();
                    $invoice->sendEmail();
                    $invoice->setEmailSent(true);
                    $invoice->save();

                    $transactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE;
                    $message = Mage::helper('payment')->__('Pagamento de %s recebido.', $order->getStore()->convertPrice($amount, true, false));
                    $order->setIsInProcess(true)->save();
                    $payment->addTransaction($transactionType, null, false, $message);

                    return $this;
                }
            }
            else
            {
                Mage::throwException(Mage::helper('transparente')->__('Ocorreu um erro ao consultar pedido %s: Msg de erro: %s', $increment_id, $consult['error']));
            }


           
        return $this;
              
    }

    // gravar dados do pedido na tabela moip e na transcation 

    public function paymentCapture($order, $moip_order, $payment, $moip_payment)
    {
        if ($order->getIncrementId())
        {
            
            $moip_order_id = $moip_order['id'];
            
            $ambiente = Mage::getSingleton('transparente/standard')->getConfigData('ambiente');
            $model = Mage::getModel('transparente/transparente');
            $total = $moip_payment['amount']['total'] / 100;
            $fees = $moip_payment['amount']['fees'] / 100;
            $moip_pay_id = $moip_payment['id'];
            $moip_boleto_printHref = $moip_payment['_links']['payBoleto']['printHref'];
            $moip_boleto_expirationDate = $moip_payment['fundingInstrument']['boleto']['expirationDate'];
            $moip_boleto_lineCode = $moip_payment['fundingInstrument']['boleto']['lineCode'];
            
            $additional_data = ['ambiente' => $ambiente, 'moip_order_id' => $moip_order_id, 'moip_pay_id' => $moip_pay_id, 'total' => $total, 'print_href' => $moip_boleto_printHref, 'expiration_date' => Mage::app()->getLocale()->date($moip_boleto_expirationDate, null, null, true)->toString('dd/MM/Y') , 'line_code' => $moip_boleto_lineCode];
            $payment->setQuotePaymentId($moip_order_id)->setParentTransactionId($moip_order_id);
            $transaction = Mage::getModel('sales/order_payment_transaction');
            $transaction->setTxnId($moip_order_id)->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER)->setPaymentId($payment->getId())->setOrderId($order->getId())->setOrderPaymentObject($payment)->setIsClosed(0)->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $additional_data)->save();
            return $this;
        }
        else
        {
            Mage::throwException('Falha ao processar seu pagamento. Por favor, entre em contato com nossa equipe.');
            return $this;
        }
    }

    public function order(Varien_Object $payment, $amount)
    {
        ignore_user_abort(true);
        $info = $this->getInfoInstance();
        $order = $payment->getOrder();

      
        $json_order = $this->getApi()->setDataMoip($order);
        $moip_order = $this->getApi()->setMoipOrder($json_order);

        if(isset($moip_order['errors'])){

           
            foreach ($moip_order['errors'] as $errors) {
                $erros_text = $errors["description"];
            }
            
            Mage::throwException(Mage::helper('transparente')->__('Encontramos alguns erros no seu cadastro. Por favor, entre em contato com nossa equipe. Erro: %s', $erros_text));
        }

        $json_payment = $this->getApi()->setJsonBoleto();
        $moip_payment = $this->getApi()->setMoipPayment($json_payment, $moip_order['id']);

        if(isset($moip_payment['errors']) || isset($moip_payment['ERROR'])){
            if(isset($moip_payment['errors'])){
               foreach ($moip_payment['errors'] as $errors) {
                    $erros_text = $errors["description"];
                } 
            } else {
                $errors_text = Mage::helper('transparente')->__('Ocorreu uma queda no serviço de processamento de pagamento, por favor tente de novo');
            }       
            
            Mage::throwException(Mage::helper('transparente')->__('Encontramos alguns ao processar seu pedido. Por favor, entre em contato com nossa equipe. Erro: %s', $erros_text));
        }
        $total = $moip_payment['amount']['total'] / 100;
        $fees = $moip_payment['amount']['fees'] / 100;
        $moip_order_id = $moip_order['id'];
        $moip_pay_id = $moip_payment['id'];
        $moip_boleto_printHref = $moip_payment['_links']['payBoleto']['printHref'];
        $moip_boleto_expirationDate = $moip_payment['fundingInstrument']['boleto']['expirationDate'];
        $moip_boleto_lineCode = $moip_payment['fundingInstrument']['boleto']['lineCode'];
        $ambiente = Mage::getSingleton('transparente/standard')->getConfigData('ambiente');
        
        $additionaldata = unserialize($info->getAdditionalData());
        $additionaldataAfter = array(
            'ambiente' => $ambiente,
            'moip_order_id' => $moip_order_id,
            'moip_pay_id' => $moip_pay_id, 
            'total' => $total, 
            'print_href' => $moip_boleto_printHref, 
            'expiration_date' => Mage::app()->getLocale()->date($moip_boleto_expirationDate, null, null, true)->toString('dd/MM/Y') , 
            'line_code' => $moip_boleto_lineCode
        );

        $additionaldata = array_merge($additionaldata, $additionaldataAfter);
        $info->setAdditionalData(serialize($additionaldata))->setAdditionalInformation(serialize($additionaldata))->save();
        $this->paymentCapture($order, $moip_order, $payment,  $moip_payment);
        $order->setExtOrderId($moip_order_id);
        return $this;
    }

    public function assignData($data)
    {
        if (!($data instanceof Varien_Object))
        {
            $data = new Varien_Object($data);
        }

        $info = $this->getInfoInstance();
        $additionaldata = array();
        $info->setAdditionalData(serialize($additionaldata))->save()->setAdditionalInformation(serialize($additionaldata))->save();
        return $this;
    }

    
    

    public function validate()
    {
        parent::validate();
        $info = $this->getInfoInstance();
        $currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();
        $errorMsg = false;
        if ($errorMsg === false)
        {
            if (!in_array($currency_code, $this->_allowCurrencyCode))
            {
                Mage::throwException(Mage::helper('transparente')->__('O Moip Não pode Transacionar pedidos feitos em  (' . $currency_code . ') verifique as configurações de Moeda do seu magento.'));
            }
        }

        if ($errorMsg)
        {
            Mage::throwException($errorMsg);
        }
        return $this;
    }
}