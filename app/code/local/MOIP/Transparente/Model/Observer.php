<?php
class Moip_Transparente_Model_Observer
{
    
    public function getOrder($id)
    {
        $final = "";
        $current_order = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('increment_id', $id);
        if ($current_order) {
            foreach ($current_order as $order) {
                $final = $order;
                break;
            }
        }
        return $final;
    }



    public function paymentCapture($observer){
        
        $pgto             = Mage::getSingleton('checkout/session')->getMoipPayment();

        $api = $this->getApi();
        $order = $observer['token_moip'];
        $decode_order = json_decode($order);
        $order = $this->getOrder($decode_order->ownId);
        if($order){
            $payment = $order->getPayment();
            $mage_pay         = $order->getId();
            $forma_pagamento  = $payment->getMethodInstance()->getCode();
            $this->setInit($order);
            $api->generateLog($mage_pay, 'MOIP_Save.log');
            $api->generateLog($forma_pagamento, 'MOIP_Save.log');


            if ($forma_pagamento == "moip_boleto" || $forma_pagamento == "moip_tef" || $forma_pagamento == "moip_cc") {
                $pgto             = $observer;
                
                $orderIdMoip      = $pgto['order_moip'];
                $email            = $order->getBillingAddress()->getEmail();
                $customerId       = $order->getCustomerId();

                $responseMoip     = $pgto['response_moip'];
                $fees             = $responseMoip->amount->fees;
                $moipidPay        = $responseMoip->id;
                
                if($forma_pagamento == "moip_cc"){
                    $infopgto = unserialize($pgto['card_save']);
                    
                }
                 
            } else {
                return $this; 
            }
            if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste"){
                $ambiente = "teste";
            }
            else{
                $ambiente = "producao";
            }
            $model = Mage::getModel('transparente/write');
            $order_moip = str_replace("ORD-", "", $orderIdMoip);
            $model->setMagePay($mage_pay)->setMoipOrder($order_moip)->setCustomerEmail($email)->setCustomerId($customerId)->setFormaPagamento($forma_pagamento)->setMoipAmbiente($ambiente)->setMoipFees($fees)->setMoipPay($moipidPay);
            if ($forma_pagamento == "moip_boleto") {
                $href                       = $responseMoip->_links->payBoleto->redirectHref;
                $moip_boleto_expirationDate = $responseMoip->fundingInstrument->boleto->expirationDate;
                $moip_boleto_lineCode       = $responseMoip->fundingInstrument->boleto->lineCode;

                $api->generateLog($href, 'MOIP_Save.log');
                $api->generateLog($moip_boleto_expirationDate, 'MOIP_Save.log');
                $api->generateLog($moip_boleto_lineCode, 'MOIP_Save.log');

                $model->setMoipHrefBoleto($href)->setMoipExpirationBoleto($moip_boleto_expirationDate)->setMoipLinecodeBoleto($moip_boleto_lineCode);
            } elseif ($forma_pagamento == "moip_tef") {
                $href                       = $responseMoip->_links->payOnlineBankDebitItau->redirectHref;
                $moip_transf_expirationDate = $responseMoip->fundingInstrument->onlineBankDebit->expirationDate;
                $moip_transf_bankName       = $responseMoip->fundingInstrument->onlineBankDebit->bankName;
                $model->setMoipHrefTrans($href)->setMoipBankNameTrans($moip_transf_bankName)->setMoipExpirationTrans($moip_transf_expirationDate);
            } elseif ($forma_pagamento == "moip_cc") {
                $moip_card_installmentCount = $responseMoip->installmentCount;
                $moip_card_brand            = $responseMoip->fundingInstrument->creditCard->brand;
                if($infopgto['save_card']){
                    $moip_card_id               = $responseMoip->fundingInstrument->creditCard->id;
                } else {
                    $moip_card_id               = null;
                }
                $moip_card_first6           = $responseMoip->fundingInstrument->creditCard->first6;
                $moip_card_last4            = $responseMoip->fundingInstrument->creditCard->last4;
                $moip_card_birthdate        = $responseMoip->fundingInstrument->creditCard->holder->birthdate;
                $moip_card_taxDocument      = $responseMoip->fundingInstrument->creditCard->holder->taxDocument->number;
                $moip_card_fullname         = $responseMoip->fundingInstrument->creditCard->holder->fullname;
                $model->setMoipCardInstallment($moip_card_installmentCount)->setMoipCardBrand($moip_card_brand)->setMoipCardId($moip_card_id)->setMoipCardFirst6($moip_card_first6)->setMoipCardLast4($moip_card_last4)->setMoipCardBirthdate($moip_card_birthdate)->setMoipCardTaxdocument($moip_card_taxDocument)->setMoipCardFullname($moip_card_fullname);
            }
            $model->save();

            return $this;

        }
    }

    public function setInit($order){
        if($order){

            if($this->getModuleConfig('type_status_init') == "onhold") {
                $mage_pay         = $order->getId();
                $forma_pagamento  = $order->getPayment()->getMethodInstance()->getCode();
                if ($forma_pagamento == "moip_boleto" || $forma_pagamento == "moip_tef" || $forma_pagamento == "moip_cc") {
                  if ($forma_pagamento == "moip_boleto") {
                        $state_onhold               = $this->getModuleConfig('order_status_holded_boleto');
                        $comment                    = "Aguardando confirmação automática de pagamento.";
                    } elseif ($forma_pagamento == "moip_tef") {
                        
                        $state_onhold               = $this->getModuleConfig('order_status_holded_tef');
                        $comment                    = "Aguardando confirmação automática de pagamento.";
                        
                    } elseif ($forma_pagamento == "moip_cc") {
                        $state_onhold               = $this->getModuleConfig('order_status_holded');
                        $comment                    = "Aguardando confirmação automática de pagamento.";
                    }
                } else {
                    return;
                }

                
                $order->setState(Mage_Sales_Model_Order::STATE_HOLDED, $state_onhold, $comment, $notified = false, $includeComment = true);
                $order->save();
                
                #$order->sendOrderUpdateEmail(true, $comment);
                return $this;
            }

            if($this->getModuleConfig('type_status_init') == "pending_payment") {
                $mage_pay         = $order->getId();
                $forma_pagamento  = $order->getPayment()->getMethodInstance()->getCode();
                if ($forma_pagamento == "moip_boleto" || $forma_pagamento == "moip_tef" || $forma_pagamento == "moip_cc") {
                  if ($forma_pagamento == "moip_boleto") {
                        $state_onhold               = $this->getModuleConfig('order_status_pending_payment_boleto');
                        $comment                    = "Aguardando confirmação automática de pagamento.";
                    } elseif ($forma_pagamento == "moip_tef") {
                        
                        $state_onhold               = $this->getModuleConfig('order_status_pending_payment_tef');
                        $comment                    = "Aguardando confirmação automática de pagamento.";
                        
                    } elseif ($forma_pagamento == "moip_cc") {
                        $state_onhold               = $this->getModuleConfig('order_status_pending_payment');
                        $comment                    = "Aguardando confirmação automática de pagamento.";
                    }
                } else {
                    return;
                }

                
                $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, $state_onhold, $comment, $notified = false, $includeComment = true);
                $order->save();
                
                #$order->sendOrderUpdateEmail(true, $comment);
                return $this;
            }

            if($this->getModuleConfig('type_status_init') ==  "not"){
                 $order->save();
            }
            
        }
    }
    public function getModuleConfig($value){
        return Mage::getSingleton('transparente/standard')->getConfigData($value);
    }
    public function getMoipPayment()
    {
        return $this->getCheckout()->getMoipData();
    }

    

    public function setStatusAll() {
            
            $api                        = $this->getApi();
            $to                         = now();
            $moip_boleto_vencimento     =  Mage::getStoreConfig('payment/moip_boleto/vcmentoboleto') + 7;
            $time_boleto                = '-'.(int)$moip_boleto_vencimento.' day';
            $from_boleto                = date('Y-m-d',(strtotime($time_boleto, strtotime($to))));
            $from_date                  = date('Y-m-d H:i:s', strtotime("$from_boleto 00:00:00"));
            $to_date                    = date('Y-m-d H:i:s', strtotime("$from_boleto 23:59:59"));

            $api->generateLog("------- Set no state -------", 'MOIP_StateAll.log');
            $api->generateLog($time_boleto, 'MOIP_StateAll.log');
            $api->generateLog($from_date, 'MOIP_StateAll.log');
            $api->generateLog($to_date, 'MOIP_StateAll.log');
            
            
            $orders = Mage::getModel("sales/order")->getCollection()->join(
                                    array('payment' => 'sales/order_payment'),
                                    'main_table.entity_id=payment.parent_id',
                                    array('payment_method' => 'payment.method')
                                );
            $orders->addFieldToFilter('created_at', array('gteq' => $from_date))
                         ->addFieldToFilter('created_at', array('lteq' => $to_date))
                         ->addAttributeToFilter('status',  array(
                                                                                'nin' => array(
                                                                                                    Mage_Sales_Model_Order::STATE_COMPLETE,
                                                                                                    Mage_Sales_Model_Order::STATE_PROCESSING,
                                                                                                    Mage_Sales_Model_Order::STATE_CLOSED,
                                                                                                    Mage_Sales_Model_Order::STATE_CANCELED
                                                                                                )
                                                                                )
                                                                
                                                )
                         ->addAttributeToFilter('payment.method', array(array('eq' => 'moip_cc'), array('eq' => 'moip_boleto'), array('eq' => 'moip_tef')));

            foreach($orders as $order){
                 $order =  Mage::getModel('sales/order')->load($order->getEntityId());
                 $this->setStateAll($order);
            }
    }

    public function setStateAll($order){
        $standard = $this->getStandard();
        $api = $this->getApi();
        $model = Mage::getModel('transparente/write');
        $api->generateLog("------- Set no state -------", 'MOIP_StateAll.log');
        $api->generateLog($order->getId(), 'MOIP_StateAll.log');
        $api->generateLog($moip_pay, 'MOIP_StateAll.log');
        $result = $model->load($order->getId(), 'mage_pay');
        $moip_pay = $result->getMoipPay();
        if($moip_pay){
            $url = "https://api.moip.com.br/v2/webhooks?resourceId=".$moip_pay;
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod');
            $header = "Authorization: OAuth " . $oauth;
            $documento = 'Content-Type: application/json; charset=utf-8';
            $result = array();
            $ch     = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                $header
            ));
            curl_setopt($ch, CURLOPT_USERAGENT, 'MoipMagento/2.0.0');
            $responseBody = curl_exec($ch);
            $info_curl = curl_getinfo($ch);
            curl_close($ch);
            $api->generateLog($moip_pay, 'MOIP_StateAll.log');
    
            foreach (json_decode($responseBody, true) as $key => $value) {
                foreach ($value as $key => $_value) {
                            $api->generateLog($_value['event'], 'MOIP_CRON.log');
                    if($_value['event'] == "PAYMENT.AUTHORIZED"){
                        $paid = $standard->getConfigData('order_status_processing');
                        $upOrder = $this->autorizaPagamento($order, $paid);
                        $autorize_pagamento = 1;
                    } elseif ($_value['event'] == "PAYMENT.CANCELLED") {
    
                                if($order->canUnhold()) {
                                    $order->unhold()->save();
                                }
    
                                $order->cancel()->save();
                                $link = Mage::getUrl('sales/order/reorder/');
                                $link = $link.'order_id/'.$order->getEntityId();
                                $comment = "Cancelado por tempo limite para a notificação de pagamento, caso já tenha feito o pagamento entre em contato com o nosso atendimento, se desejar poderá refazer o seu pedido acessando: ".$link;
                                $status = 'canceled';
                                $order->cancel();
                                $state = Mage_Sales_Model_Order::STATE_CANCELED;
                                $order->setState($state, $status, $comment, $notified = true, $includeComment = true);
                                $order->sendOrderUpdateEmail(true, $comment);
                                $order->save();
                    } else {
                        return;
                    }
                }
                
            }
            return;
        } else {
                 return;
        }
    }   

    public function setHoldedNotification(){
            $api                        = $this->getApi();
            $standard                   = $this->getStandard();
            $to                         = now();
            $moip_boleto_vencimento     =  Mage::getStoreConfig('payment/moip_boleto/vcmentoboleto');
            $time_boleto                = '-'.(int)$moip_boleto_vencimento.' day';
            $from_boleto                = date('Y-m-d',(strtotime($time_boleto, strtotime($to))));
            $from_date                  = date('Y-m-d H:i:s', strtotime("$from_boleto 00:00:00"));
            $to_date                    = date('Y-m-d H:i:s', strtotime("$from_boleto 23:59:59"));

            $api->generateLog('----- consulta pedidos em holded', 'MOIP_CronNotification.log');
            $api->generateLog($from_date, 'MOIP_CronNotification.log');
            $api->generateLog($to_date, 'MOIP_CronNotification.log');

            $orders = Mage::getModel("sales/order")->getCollection()->join(
                                    array('payment' => 'sales/order_payment'),
                                    'main_table.entity_id=payment.parent_id',
                                    array('payment_method' => 'payment.method')
                                );
            $orders->addFieldToFilter('created_at', array('gteq' => $from_date))
                         ->addFieldToFilter('created_at', array('lteq' => $to_date))
                         ->addAttributeToFilter('status',  array(
                                                                                'nin' => array(
                                                                                                    Mage_Sales_Model_Order::STATE_COMPLETE,
                                                                                                    Mage_Sales_Model_Order::STATE_PROCESSING,
                                                                                                    Mage_Sales_Model_Order::STATE_CLOSED,
                                                                                                    Mage_Sales_Model_Order::STATE_CANCELED
                                                                                                )
                                                                                )
                                                                
                                                )
                         ->addAttributeToFilter('payment.method', array(array('eq' => 'moip_boleto')));

        foreach($orders as $order){
             $api->generateLog($order->getEntityId(), 'MOIP_CronNotification.log');
             $order =  Mage::getModel('sales/order')->load($order->getEntityId());
             $onhold = $standard->getConfigData('order_status_holded_boleto');
             $state = Mage_Sales_Model_Order::STATE_HOLDED;
             $status = $onhold;
             $comment = "Seu boleto está próximo a vencer, caso não tenha realizado o pagamento ainda, acesse sua conta e realize agora.";
             $update = $this->updateInOrder($order, $state, $status, $comment);
        }
        
    }



    public function getPriceBundle($product) {
        
           $grouped_product_model = Mage::getModel('catalog/product_type_grouped');
        $groupedParentId = $grouped_product_model->getParentIdsByChild($product->getId());
        $_associatedProducts = $product->getTypeInstance(true)->getAssociatedProducts($product);

        foreach($_associatedProducts as $_associatedProduct) {
            if($ogPrice = $_associatedProduct->getPrice()) {
                $ogPrice = $_associatedProduct->getPrice();
            }
        }

        return $ogPrice;
    }

    public function addMassAction($observer)
    {
        $block = $observer->getEvent()->getBlock();
        if(get_class($block) =='Mage_Adminhtml_Block_Widget_Grid_Massaction'
            && $block->getRequest()->getControllerName() == 'sales_order')
        {
            $block->addItem('transparente', array(
                'label' => 'Consultar status no Moip',
                'url' =>  Mage::helper('adminhtml')->getUrl('adminhtml/adminhtml_statusmoip/setstate'),
            ));
        }
    }

    public function getApi()
    {
        $api = Mage::getModel('transparente/api');
        return $api;
    }

    public function getStandard() {
        return Mage::getSingleton('transparente/standard');
    }


     public function autorizaPagamento($order, $paid){
        if($order->canUnhold()) {
            $order->unhold()->save();
        }
        
        $invoice = $order->prepareInvoice();
        if ($this->getStandard()->canCapture())
        {
                $invoice->register()->capture();
        }
        Mage::getModel('core/resource_transaction')->addObject($invoice)->addObject($invoice->getOrder())->save();
        $invoice->sendEmail();
        $invoice->setEmailSent(true);
        $invoice->save();
        return;
     }

     public function updateInOrder($order, $state, $status, $comment){
        $order->setState($state, $status, $comment, $notified = true, $includeComment = true);
        $order->save();
        $order->sendOrderUpdateEmail(true, $comment);
        return true;
     }
     

    public function catalog_product_save_after_plans($observer){
        $product = $observer->getProduct();
        if($product->getIsRecurring()){
            $recurring = $product->getRecurringProfile();
            
            if($recurring['init_amount']){
                $setup = number_format($recurring['init_amount'], 2, '', '');    
            } else {
                $setup = $recurring['init_amount'];    
            }
            if($recurring['trial_period_frequency']){
                if($recurring['trial_period_unit'] ==  'day')
                    $trial_date = $recurring['trial_period_frequency'];
                elseif($recurring['trial_period_unit'] ==  'week')
                     $trial_date = $recurring['trial_period_frequency']*7;
                 elseif($recurring['trial_period_unit'] ==  'semi_month')
                     $trial_date = $recurring['trial_period_frequency']*14;
                  elseif($recurring['trial_period_unit'] ==  'month')
                       $trial_date = $recurring['trial_period_frequency']*30;
                   elseif($recurring['trial_period_unit'] ==  'year')
                       $trial_date = $recurring['trial_period_frequency']*360;

            } else {
                       $trial_date = 0;                
            }

           
            if($product->getTypeId() != "grouped"){
                $data = array(
                            'code' => $product->getSku(),
                            'name' => $product->getName(),
                            'description' => $recurring['schedule_description'],
                            'max_qty' => $recurring['period_max_cycles'],
                            'amount' => number_format($product->getFinalPrice(), 2, '', ''),
                            'setup_fee' => $setup,
                            'interval' => array(
                                                    'length' => $recurring['period_frequency'],
                                                    'unit' => $recurring['period_unit'],
                                                ),
                            'billing_cycles' => $recurring['period_max_cycles'],
                            
                            'status'    => 'ACTIVE',
                            'payment_method' => 'ALL',
                             );
            } else {
                $data = array(
                            'code' => $product->getSku(),
                            'name' => $product->getName(),
                            'description' => $recurring['schedule_description'],
                            'max_qty' => $recurring['period_max_cycles'],
                            'amount' => number_format($this->getPriceBundle($product), 2, '', ''),
                            'setup_fee' => $setup,
                            'interval' => array(
                                                    'length' => $recurring['period_frequency'],
                                                    'unit' => $recurring['period_unit'],
                                                ),
                            'billing_cycles' => $recurring['period_max_cycles'],
                            'status'    => 'ACTIVE',
                            'payment_method' => 'ALL',
                            

                             );
            }
            
            $api = Mage::getSingleton('transparente/recurringapi');
            $plans_data = $api->ConsultPlans($data);
           
            
            return;
        }
        
    }
}