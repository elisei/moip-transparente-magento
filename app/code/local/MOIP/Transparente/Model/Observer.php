<?php
class Moip_Transparente_Model_Observer
{
    
    public function setStateAll($order){
        $standard = $this->getStandard();
        $api = $this->getApi();
        $model = Mage::getModel('transparente/write');
        $api->generateLog("------- CONSULTA -------", 'MOIP_CRON.log');
        $api->generateLog($order->getId(), 'MOIP_CRON.log');
        $api->generateLog($moip_pay, 'MOIP_CRON.log');
        $result = $model->load($order->getId(), 'mage_pay');
        $moip_pay = $result->getMoipPay();
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
        $api->generateLog($moip_pay, 'MOIP_CRON.log');

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
        
    }

    public function setStatusCcAll() {
            $api = $this->getApi();
            $api->generateLog("aciona", 'MOIP_CRON.log');
            
            
            $to = date('Y-m-d', time());

            $moip_boleto_vencimento = 3;
            $time_boleto = '-'.(int)$moip_boleto_vencimento.' day';


            $from_boleto = date('Y-m-d',(strtotime($time_boleto, strtotime($to))));

            
            $from_date = date('Y-m-d H:i:s', strtotime("$from_boleto 00:00:00"));
            $to_date = date('Y-m-d H:i:s', strtotime("$from_boleto 23:59:59"));


          

            $api->generateLog($from_date, 'MOIP_CRON.log');
            $api->generateLog($to_date, 'MOIP_CRON.log');
            $orders = Mage::getModel("sales/order")->getCollection()->join(
                                    array('payment' => 'sales/order_payment'),
                                    'main_table.entity_id=payment.parent_id',
                                    array('payment_method' => 'payment.method')
                                );
                    $orders->addFieldToFilter('created_at', array('gteq' => $from_date))
                            ->addFieldToFilter('created_at', array('lteq' => $to_date))
                            ->addFieldToFilter('payment.method', array(array('eq' => 'moip_cc')))
                             >addAttributeToFilter('status', array('neq' => array('canceled','complete','closed')));

            foreach($orders as $order){
                 $order =  Mage::getModel('sales/order')->load($order->getEntityId());
                 $this->setStateAll($order);
            }
    }



    public function setStatusBoletoAll() {
            $api = $this->getApi();
            $api->generateLog("aciona", 'MOIP_CRON.log');
            
            
            $to = date('Y-m-d', time());

            $moip_boleto_vencimento =  Mage::getStoreConfig('payment/moip_transparente_standard/vcmentoboleto') + 6;
            $time_boleto = '-'.(int)$moip_boleto_vencimento.' day';


            $from_boleto = date('Y-m-d',(strtotime($time_boleto, strtotime($to))));

            
            $from_date = date('Y-m-d H:i:s', strtotime("$from_boleto 00:00:00"));
            $to_date = date('Y-m-d H:i:s', strtotime("$from_boleto 23:59:59"));


          

            $api->generateLog($from_date, 'MOIP_CRON.log');
            $api->generateLog($to_date, 'MOIP_CRON.log');
            $orders = Mage::getModel("sales/order")->getCollection()->join(
                                    array('payment' => 'sales/order_payment'),
                                    'main_table.entity_id=payment.parent_id',
                                    array('payment_method' => 'payment.method')
                                );
                    $orders->addFieldToFilter('created_at', array('gteq' => $from_date))
                            ->addFieldToFilter('created_at', array('lteq' => $to_date))
                            ->addFieldToFilter('payment.method', array(array('eq' => 'moip_boleto')))
                             >addAttributeToFilter('status', array('neq' => array('canceled','complete','closed')));

            foreach($orders as $order){
                 $order =  Mage::getModel('sales/order')->load($order->getEntityId());
                 $this->setStateAll($order);
            }
    }

    public function  setStatusTefAll() {
            $api = $this->getApi();
            $api->generateLog("aciona", 'MOIP_CRON.log');
            
            
            $to = date('Y-m-d', time());

            $moip_boleto_vencimento =  Mage::getStoreConfig('payment/moip_transparente_standard/vcmentoboleto') + 5;
            $time_boleto = '-'.(int)$moip_boleto_vencimento.' day';


            $from_boleto = date('Y-m-d',(strtotime($time_boleto, strtotime($to))));

            
            $from_date = date('Y-m-d H:i:s', strtotime("$from_boleto 00:00:00"));
            $to_date = date('Y-m-d H:i:s', strtotime("$from_boleto 23:59:59"));


          

            $api->generateLog($from_date, 'MOIP_CRON.log');
            $api->generateLog($to_date, 'MOIP_CRON.log');
            $orders = Mage::getModel("sales/order")->getCollection()->join(
                                    array('payment' => 'sales/order_payment'),
                                    'main_table.entity_id=payment.parent_id',
                                    array('payment_method' => 'payment.method')
                                );
                    $orders->addFieldToFilter('created_at', array('gteq' => $from_date))
                            ->addFieldToFilter('created_at', array('lteq' => $to_date))
                            ->addFieldToFilter('payment.method', array(array('eq' => 'moip_tef')))
                             >addAttributeToFilter('status', array('neq' => array('canceled','complete','closed')));

            foreach($orders as $order){
                 $order =  Mage::getModel('sales/order')->load($order->getEntityId());
                 $this->setStateAll($order);
            }
    }

    public function setStatusAll() {
            $api = $this->getApi();
            $api->generateLog("aciona", 'MOIP_CRON.log');
            
            
            $to = date('Y-m-d', time());

            $moip_boleto_vencimento =  Mage::getStoreConfig('payment/moip_transparente_standard/vcmentoboleto') + 7;
            $time_boleto = '-'.(int)$moip_boleto_vencimento.' day';


            $from_boleto = date('Y-m-d',(strtotime($time_boleto, strtotime($to))));

            
            $from_date = date('Y-m-d H:i:s', strtotime("$from_boleto 00:00:00"));
            $to_date = date('Y-m-d H:i:s', strtotime("$from_boleto 23:59:59"));


          

            $api->generateLog($from_date, 'MOIP_CRON.log');
            $api->generateLog($to_date, 'MOIP_CRON.log');
            $orders = Mage::getModel("sales/order")->getCollection()->join(
                                    array('payment' => 'sales/order_payment'),
                                    'main_table.entity_id=payment.parent_id',
                                    array('payment_method' => 'payment.method')
                                );
                    $orders->addFieldToFilter('created_at', array('gteq' => $from_date))
                            ->addFieldToFilter('created_at', array('lteq' => $to_date))
                            ->addFieldToFilter('payment.method', array(array('eq' => 'moip_cc'), array('eq' => 'moip_boleto'), array('eq' => 'moip_tef')))
                             >addAttributeToFilter('status', array('neq' => array('canceled','complete','closed')));

            foreach($orders as $order){
                 $order =  Mage::getModel('sales/order')->load($order->getEntityId());
                 $this->setStateAll($order);
            }
    }


    public function setHoldedNotification(){
        $api = $this->getApi();
        $api->generateLog("----- Enviando Boleto a vencer -----", 'MOIP_CRON.log');


        $to = date('Y-m-d', time());

        $moip_boleto_vencimento =  Mage::getStoreConfig('payment/moip_transparente_standard/vcmentoboleto');
        $time_boleto = '-'.(int)$moip_boleto_vencimento.' day';


        $from_boleto = date('Y-m-d',(strtotime($time_boleto, strtotime($to))));


        $from_date = date('Y-m-d H:i:s', strtotime("$from_boleto 00:00:00"));
        $to_date = date('Y-m-d H:i:s', strtotime("$from_boleto 23:59:59"));




        $api->generateLog($from_date, 'MOIP_CRON.log');
        $api->generateLog($to_date, 'MOIP_CRON.log');
        $orders = Mage::getModel("sales/order")->getCollection()->join(
                                array('payment' => 'sales/order_payment'),
                                'main_table.entity_id=payment.parent_id',
                                array('payment_method' => 'payment.method')
                            );
                $orders->addFieldToFilter('created_at', array('gteq' => $from_date))
                        ->addFieldToFilter('created_at', array('lteq' => $to_date))
                        ->addFieldToFilter('payment.method', array(array('eq' => 'moip_boleto')))
                         >addAttributeToFilter('status', array('neq' => array('canceled','complete','closed')));

        foreach($orders as $order){
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
