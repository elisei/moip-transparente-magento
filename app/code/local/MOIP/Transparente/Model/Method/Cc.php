<?php
class MOIP_Transparente_Model_Method_Cc extends Mage_Payment_Model_Method_Abstract
{
    const METHOD_CODE = 'moip_cc';
    const MOIP_AUTHORIZED = 'AUTHORIZED';
    const MOIP_PRE_AUTHORIZED = 'PRE_AUTHORIZED';
    const MOIP_CANCELLED = 'CANCELLED';
    protected $_code = self::METHOD_CODE;
    protected $_formBlockType = 'transparente/form_cc';
    protected $_infoBlockType = 'transparente/info_cc';
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canFetchTransactionInfo = true;
    protected $_isInitializeNeeded = true;
    protected $_canSaveCc = true;
    protected $_allowCurrencyCode = array('BRL');
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_canManageRecurringProfiles = false;

    public function getPayment()
    {
        return $this->getQuote()->getPayment();
    }

    public function getCheckout()
    {
        
        if(!Mage::app()->getStore()->isAdmin()) {
            return Mage::getSingleton('checkout/session');
        } else {
            return Mage::getSingleton('adminhtml/session_quote');
        }

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

            $moip_init_status           = $moip_payment['status'];
            if($moip_init_status == self::MOIP_CANCELLED){
                $moip_init_status_descrip = $moip_payment['cancellationDetails']['description']; 
            } else {
                $moip_init_status_descrip = "";
            }

            $moip_card_first6           = $moip_payment['fundingInstrument']['creditCard']['first6'];
            $moip_card_last4            = $moip_payment['fundingInstrument']['creditCard']['last4'];
            $moip_card_birthdate        = $moip_payment['fundingInstrument']['creditCard']['holder']['birthdate'];
          /*  $moip_card_taxDocument      = $moip_payment['fundingInstrument']['creditCard']['holder']['taxDocument']['number'];*/
            $moip_card_fullname         = $moip_payment['fundingInstrument']['creditCard']['holder']['fullname'];
            $moip_card_installmentCount = $moip_payment['installmentCount'];
            $moip_card_brand            = $moip_payment['fundingInstrument']['creditCard']['brand'];
            $moip_card_id               = $moip_payment['fundingInstrument']['creditCard']['id'];

           
            $additional_data = array(
                'ambiente' => $ambiente,
                'moip_order_id' => $moip_order_id,
                'moip_pay_id' => $moip_pay_id, 
                'fees' => $fees,
                'total' => $total,
                'moip_init_status' => $moip_init_status,
                'moip_init_status_descrip' => $moip_init_status_descrip,
                'moip_card_first6' => $moip_card_first6, 
                'moip_card_last4' => $moip_card_last4, 
                /*'moip_card_taxDocument' => $moip_card_taxDocument,*/
                'moip_card_fullname' => $moip_card_fullname,
                'moip_card_installmentCount' => $moip_card_installmentCount,
                'moip_card_brand' => $moip_card_brand,
                'moip_card_id' => $moip_card_id
            );
            //pretendo recuperar o order moip caso já o tenha antes, assim faço apenas pay quando necessário... 
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

        $json_payment = $this->getApi()->setJsonCc($info, $order);
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
        $total                  = $moip_payment['amount']['total'] / 100;
        $fees                   = $moip_payment['amount']['fees'] / 100;
        $moip_order_id          = $moip_order['id'];
        $moip_pay_id            = $moip_payment['id'];
        $ambiente               = Mage::getSingleton('transparente/standard')->getConfigData('ambiente');
        $moip_init_status       = $moip_payment['status'];

        if($moip_init_status == self::MOIP_CANCELLED){
            $moip_init_status_descrip   = $moip_payment['cancellationDetails']['description']; 
        } else {
            $moip_init_status_descrip   = "";
        }
        $moip_card_first6           = $moip_payment['fundingInstrument']['creditCard']['first6'];
        $moip_card_last4            = $moip_payment['fundingInstrument']['creditCard']['last4'];
        $moip_card_birthdate        = $moip_payment['fundingInstrument']['creditCard']['holder']['birthdate'];
        /*$moip_card_taxDocument      = $moip_payment['fundingInstrument']['creditCard']['holder']['taxDocument']['number'];*/
        $moip_card_fullname         = $moip_payment['fundingInstrument']['creditCard']['holder']['fullname'];
        $moip_card_installmentCount = $moip_payment['installmentCount'];
        $moip_card_brand            = $moip_payment['fundingInstrument']['creditCard']['brand'];
        $moip_card_id               = $moip_payment['fundingInstrument']['creditCard']['id'];

        $additionaldata = unserialize($info->getAdditionalData());

        $additionaldataAfter = array(
            'ambiente' => $ambiente,
            'moip_order_id' => $moip_order_id,
            'moip_pay_id' => $moip_pay_id, 
            'fees' => $fees,
            'total' => $total, 
            'moip_init_status' => $moip_init_status,
            'moip_init_status_descrip' => $moip_init_status_descrip,
            'moip_card_first6' => $moip_card_first6,
            'moip_card_last4' => $moip_card_last4, 
           /* 'moip_card_taxDocument' => $moip_card_taxDocument,*/
            'moip_card_fullname' => $moip_card_fullname,
            'moip_card_installmentCount' => $moip_card_installmentCount,
            'moip_card_brand' => $moip_card_brand,
            'moip_card_id' => $moip_card_id
        );
        $additionaldata = array_merge($additionaldata, $additionaldataAfter);
        $info->setCcType($moip_card_brand)->setCcOwner($moip_card_fullname)->setCcNumber($moip_card_first6)->setCcLast4($moip_card_last4)->setCcNumberEnc($moip_card_id)->setAdditionalData(serialize($additionaldata))->setAdditionalInformation(serialize($additionaldata))->setCcDebugResponseBody(serialize($moip_payment))->save();
        $this->paymentCapture($order, $moip_order, $payment,  $moip_payment);
        if($additionaldata['save_card'] == 1) {
            $this->MoipSaveCreditCart($order, $moip_payment);
        }
        $order->setExtOrderId($moip_order_id);
        return $this;
    }

    public function MoipSaveCreditCart($order, $moip_payment){
        if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste"){
            $ambiente = "teste";
        }
        else{
            $ambiente = "producao";
        }
        $model                      = Mage::getModel('transparente/transparente');
        $customerId                 = $order->getCustomerId();
        $moip_card_first6           = $moip_payment['fundingInstrument']['creditCard']['first6'];
        $moip_card_last4            = $moip_payment['fundingInstrument']['creditCard']['last4'];
        $moip_card_fullname         = $moip_payment['fundingInstrument']['creditCard']['holder']['fullname'];
        $moip_card_brand            = $moip_payment['fundingInstrument']['creditCard']['brand'];
        $moip_card_id               = $moip_payment['fundingInstrument']['creditCard']['id'];


        $model->setMoipAmbiente($ambiente)->setCustomerId($customerId)->setMoipCardBrand($moip_card_brand)->setMoipCardId($moip_card_id)->setMoipCardFirst6($moip_card_first6)->setMoipCardLast4($moip_card_last4)->setMoipCardFullname($moip_card_fullname)->save();
        return $this;
    }

    public function validate()
    {
        parent::validate();
        $info           = $this->getInfoInstance();
        $currency_code  = Mage::app()->getStore()->getCurrentCurrencyCode();
        $errorMsg       = false;
        $additionaldata = unserialize($info->getAdditionalData());
        if ($additionaldata['use_cofre'] == 0) {
            if ($errorMsg === false) {
                 if (!$info->getCcOwner()) {
                    $errorMsg = Mage::helper('transparente')->__('O nome do portador do cartão não está correto');
                }
            }
            if ($errorMsg === false) {
                if (!isset($additionaldata['hash_moip'])) {
                    $errorMsg = Mage::helper('transparente')->__('Seu cartão não pode ser processado via Moip, por favor escolha outro meio de pagamento.');
                }
            }
            if ($errorMsg === false) {
                if (!$this->getValidaCPF($additionaldata['taxdocument_moip'])) {
                    $errorMsg = Mage::helper('transparente')->__('Informe um CPF válido, caso seu cartão seja empresarial é necessário informar o seu CPF, para isto desmarque a opção "Sou o titular do cartão".');
                }
            }
            if ($errorMsg === false) {
                if (!$this->_validateExpDate($info->getCcExpYear(), $info->getCcExpMonth())) {
                    $errorMsg = $year . Mage::helper('transparente')->__('Seu Cartão está vencido, verifique se data está corretamente preenchida.');
                }
            }
            if ($errorMsg === false) {
                if (!in_array($currency_code, $this->_allowCurrencyCode)) {
                    Mage::throwException(Mage::helper('transparente')->__('O Moip Não pode Transacionar pedidos feitos em  (' . $currency_code . ') verifique as configurações de Moeda do seu magento.'));
                }
            }
            if ($errorMsg === false) {
                if (!$additionaldata['installmentcount_moip']) {
                    $errorMsg = Mage::helper('transparente')->__('Selecione o número de parcelas para o pagamento.');
                }
            }
        } elseif ($additionaldata['use_cofre'] == 1) {
            if ($errorMsg === false) {
                if (!$additionaldata['credit_card_cofre_nb']) {
                    $errorMsg = Mage::helper('transparente')->__('Selecione o cartão que deseja.');
                }
            }
            if ($errorMsg === false) {
                if (!$additionaldata['credit_card_ccv']) {
                    $errorMsg = Mage::helper('transparente')->__('Informe o código de segurança do cartão selecionado.');
                }
            }
             if ($errorMsg === false) {
                if (!$additionaldata['installmentcountcofre_moip']) {
                    $errorMsg = Mage::helper('transparente')->__('Selecione o número de parcelas para o pagamento.');
                }
            }
        }
        if ($errorMsg) {
            Mage::throwException($errorMsg);
        }

        return $this;
    }

    public function getValidaCPF($cpf = null)
    {
        if (empty($cpf)) {
            return false;
        } else if ($cpf == '00000000000' || $cpf == '11111111111' || $cpf == '22222222222' || $cpf == '33333333333' || $cpf == '44444444444' || $cpf == '55555555555' || $cpf == '66666666666' || $cpf == '77777777777' || $cpf == '88888888888' || $cpf == '99999999999') {
            return false;
        } else {
            for ($t = 9; $t < 11; $t++) {
                for ($d = 0, $c = 0; $c < $t; $c++) {
                    $d += $cpf{$c} * (($t + 1) - $c);
                }
                $d = ((10 * $d) % 11) % 10;
                if ($cpf{$c} != $d) {
                    return false;
                }
            }
            return true;
        }
    }

    public function _validateExpDate($expYear, $expMonth)
    {
        $date = Mage::app()->getLocale()->date();
        if (!$expYear || !$expMonth || ($date->compareYear($expYear) == 1) || ($date->compareYear($expYear) == 0 && ($date->compareMonth($expMonth) == 1))) {
            return false;
        }
        return true;
    }

    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info           = $this->getInfoInstance();
        $dataNascimento = $data->getMoipCcOwnerExpDay() . "/" . $data->getMoipCcOwnerExpMonth() . "/" . $data->getMoipCcOwnerExpYear();
        
        $additionaldata = array(
            'taxdocument_moip' => preg_replace("/[^0-9]/", "", $data->getMoipCcTaxdocument()),
            'installmentcount_moip' => $data->getMoipCcInstallmentCount(),
            'installmentcountcofre_moip' => $data->getMoipCcCountCofre(),
            'hash_moip' => $data->getMoipCcEncrypted(),
            'credit_card_cofre_nb' => (string) $data->getMoipCcCofreNb(),
            'save_card' => (int) $data->getMoipCcSaveCard(),
            'use_cofre' => (int) $data->getMoipCcPaymentInCofre(),
            'credit_card_ccv' => $data->getMoipCcCofreId(),
            'fullname_moip' => $data->getMoipCcOwner()
        );
        $info->setCcType($data->getMoipCcType())->setCcExpMonth($data->getMoipCcExpMonth())->setCcExpYear($data->getMoipCcExpYear())->setCcOwner($data->getMoipCcOwner())->setAdditionalData(serialize($additionaldata))->save()->setAdditionalInformation(serialize($additionaldata))->save();
        $this->getMethodJuros();
        return $this;
    }

    public function getMethodJuros()
    {   
        $parcela        = null;
        $quote          = $this->getQuote();
        $info           = $quote->getPayment();
        $additionaldata = unserialize($info->getAdditionalData());
        if ($additionaldata['installmentcount_moip']) {
            $parcela = $additionaldata['installmentcount_moip'];
        } else {
            $parcela = $additionaldata['installmentcountcofre_moip'];
        }
        $this->setJurosMoip($quote, $parcela);
        return $this;
    }

    public function setJurosMoip($quote, $parcela)
    {
        if (!$quote->isVirtual()) {
            $address = $quote->getShippingAddress();
        } else {
            $address = $quote->getBillingAddress();
        }
        $address->getFeeMoip(0);
        $address->getBaseFeeMoip(0);
        $address->save();

        $total = $address->getGrandTotal();
       
        if(!$parcela==0){
            $juros = $address->getFeeMoip();
            if(!Mage::getStoreConfig('payment/moip_cc/parcelas_avancadas')){
                $installment =  Mage::helper('transparente')->getCalcInstallment($total);
            } else {
                $installment =  Mage::helper('transparente')->getComplexCalcInstallment($total);   
            }
            if($installment && $parcela){
                $balance        = $installment[$parcela]['total_interest'];
                $address->setFeeMoip($balance);
                $address->setBaseFeeMoip($balance);
                $address->setGrandTotal($address->getGrandTotal() + $balance);
                $address->setBaseGrandTotal($address->getBaseGrandTotal() + $balance);
                $address->save();    
            }
        }
        return $this;
    }
}