<?php
class Moip_Transparente_Model_Cron
{
    public function setStatusAll()
    {
        $api                        = $this->getApi();
        $to                         = now();
        $time_consult               = '-2 day';
        $time_limit                 = date('Y-m-d', (strtotime($time_consult, strtotime($to))));
        $to_date                    = date('Y-m-d H:i:s', strtotime("$time_limit 23:59:59"));

        $api->generateLog("------- Set no state -------", 'MOIP_StateAll.log');
      
        $api->generateLog($to_date, 'MOIP_StateAll.log');
            
            
        $orders = Mage::getModel("sales/order")->getCollection()->join(
                                    array('payment' => 'sales/order_payment'),
                                    'main_table.entity_id=payment.parent_id',
                                    array('payment_method' => 'payment.method')
                                );
        $orders
                         ->addFieldToFilter('created_at', array('lteq' => $to_date))
                         ->addAttributeToFilter(
                             'state',
                             array(
                                                                'nin' => array(
                                                                                    Mage_Sales_Model_Order::STATE_COMPLETE,
                                                                                    Mage_Sales_Model_Order::STATE_PROCESSING,
                                                                                    Mage_Sales_Model_Order::STATE_CLOSED,
                                                                                    Mage_Sales_Model_Order::STATE_CANCELED
                                                                                )
                                                                )
                                                                
                                                )
                         ->addAttributeToFilter('payment.method', array(array('eq' => 'moip_cc'), array('eq' => 'moip_boleto'), array('eq' => 'moip_tef')));

         foreach ($orders as $order) {

           

            $order =  Mage::getModel('sales/order')->load($order->getEntityId());
                 
            if ($order->getExtOrderId()) {
                $moip_ord = $order->getExtOrderId();
               
                $state = $order->getState();
                $order_id = $order->getIncrementId();
                $order_state = $order->getState();

                if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
                    $url = "https://sandbox.moip.com.br/v2/orders/{$moip_ord}";
                    $oauth = trim(Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev'));
                    $header = "Authorization: OAuth {$oauth}";
                } else {
                    $url = "https://api.moip.com.br/v2/orders/{$moip_ord}";
                    $oauth = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod') ;
                    $header = "Authorization: OAuth {$oauth}";
                }

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
                $response_decode = json_decode($responseBody, true);
                $api->generateLog($order_id. " state moip " .$response_decode['status'], 'MOIP_StateAll.log');

                if($response_decode['status'] == "PAID"){
                     $payment = $order->getPayment();
                     $payment->getMethodInstance()->authorize($payment,  $order->getGrandTotal());
                     $api->generateLog($order_id. " state apos ação moip " .$order->getState(), 'MOIP_StateAll.log');


                } elseif($response_decode['status'] == "NOT_PAID"){
                     if ($order->canCancel()) {
                        $details_cancel = "Indefinido";
                        $order->cancel()->save();
                        Mage::getModel('transparente/email_cancel')->sendEmail($order, $details_cancel);
                        $translate_details = Mage::helper('transparente')->__($details_cancel);
                        $msg               = Mage::helper('transparente')->__('Email de cancelamento enviado ao cliente. Motivo real: %s, motivo exibido ao cliente: %s', $details_cancel, $translate_details);
                        $order->addStatusHistoryComment($msg);
                        $order->save();

                        $api->generateLog($order_id. " state apos ação moip " .$order->getState(), 'MOIP_StateAll.log');
                        
                    }
                }
            }
        }
    }

    

    public function setPendingNotification()
    {
        $api                        = $this->getApi();
        $to                         = now();
        $moip_boleto_vencimento     =  Mage::getStoreConfig('payment/moip_boleto/vcmentoboleto');
        $time_boleto                = '-'.(int)$moip_boleto_vencimento.' day';
        $from_boleto                = date('Y-m-d', (strtotime($time_boleto, strtotime($to))));
        $from_date                  = date('Y-m-d H:i:s', strtotime("$from_boleto 00:00:00"));
        $to_date                    = date('Y-m-d H:i:s', strtotime("$from_boleto 23:59:59"));

        $api->generateLog('----- consulta pedidos em holded', 'MOIP_CronNotification.log');
        $api->generateLog($from_date, 'MOIP_CronNotification.log');
        $api->generateLog($to_date, 'MOIP_CronNotification.log');

        $orders = Mage::getModel("sales/order")->getCollection()->join(array('payment' => 'sales/order_payment'), 'main_table.entity_id=payment.parent_id', array('payment_method' => 'payment.method'));
        $orders->addFieldToFilter('created_at', array('gteq' => $from_date))
                     ->addFieldToFilter('created_at', array('lteq' => $to_date))
                     ->addAttributeToFilter('state', array('eq' => array(Mage_Sales_Model_Order::STATE_NEW)))
                     ->addAttributeToFilter('payment.method', array(array('eq' => 'moip_boleto')));

        foreach ($orders as $order) {
            if ($order->getExtOrderId()) {
                $consult = $this->getApi()->getMoipOrder($order->getExtOrderId());
                if (!isset($consult['error'])) {
                    if ($consult['status'] == MOIP_Transparente_Model_Api::MOIP_WAITING) {
                        foreach ($consult['payments'] as $moip_payment) {
                            $expirationDate = $moip_payment['fundingInstrument']['boleto']['expirationDate'];
                            $lineCode 		= $moip_payment['fundingInstrument']['boleto']['lineCode'];
                            $boletoHref 	= $moip_payment['_links']['payBoleto']['printHref'];
                        }
                        if ($expirationDate) {
                            $details = array('expirationDate' => Mage::app()->getLocale()->date($expirationDate, null, null, true)->toString('dd/MM/Y'), 'boletoHref' => $boletoHref, 'lineCode' => $lineCode);
                            Mage::getModel('transparente/email_pending')->sendEmail($order, $details);
                            $this->commentOrder($order);
                        }
                    }
                }
            }
        }
        return $this;
    }

    public function getApi()
    {
        $api = Mage::getModel('transparente/api');
        return $api;
    }

    public function commentOrder($order)
    {
        $msg = Mage::helper('transparente')->__('Email de notificação de pagamento pendente enviado');
        $order->addStatusHistoryComment($msg);
        $order->save();
        return $this;
    }
}
