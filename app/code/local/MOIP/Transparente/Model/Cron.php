<?php
class Moip_Transparente_Model_Cron
{
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
                         ->addAttributeToFilter('state',  array(
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
                 //ainda não conclui essa função por favor aguarde... 
                 //falta dar load na order, consultar status e aplicar...
                 return $this;
            }
    }

    

    public function setPendingNotification(){
        $api                        = $this->getApi();
        $to                         = now();
        $moip_boleto_vencimento     =  Mage::getStoreConfig('payment/moip_boleto/vcmentoboleto');
        $time_boleto                = '-'.(int)$moip_boleto_vencimento.' day';
        $from_boleto                = date('Y-m-d',(strtotime($time_boleto, strtotime($to))));
        $from_date                  = date('Y-m-d H:i:s', strtotime("$from_boleto 00:00:00"));
        $to_date                    = date('Y-m-d H:i:s', strtotime("$from_boleto 23:59:59"));

        $api->generateLog('----- consulta pedidos em holded', 'MOIP_CronNotification.log');
        $api->generateLog($from_date, 'MOIP_CronNotification.log');
        $api->generateLog($to_date, 'MOIP_CronNotification.log');

        $orders = Mage::getModel("sales/order")->getCollection()->join(array('payment' => 'sales/order_payment'),'main_table.entity_id=payment.parent_id',array('payment_method' => 'payment.method'));
        $orders->addFieldToFilter('created_at', array('gteq' => $from_date))
                     ->addFieldToFilter('created_at', array('lteq' => $to_date))
                     ->addAttributeToFilter('state',  array('eq' => array(Mage_Sales_Model_Order::STATE_NEW)))
                     ->addAttributeToFilter('payment.method', array(array('eq' => 'moip_boleto')));

        foreach($orders as $order){
             if($order->getExtOrderId()) {
             	$consult = $this->getApi()->getMoipOrder($order->getExtOrderId());
	            if (!isset($consult['error']))
	            {
	            	if($consult['status'] == MOIP_Transparente_Model_Api::MOIP_WAITING){

	            		foreach ($consult['payments'] as $moip_payment) {
	            			$expirationDate = $moip_payment['fundingInstrument']['boleto']['expirationDate'];
		            		$lineCode 		= $moip_payment['fundingInstrument']['boleto']['lineCode'];
		            		$boletoHref 	= $moip_payment['_links']['payBoleto']['printHref'];
	            		}
	            		if($expirationDate){
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

    public function commentOrder($order){
        $msg = Mage::helper('transparente')->__('Email de notificação de pagamento pendente enviado');
        $order->addStatusHistoryComment($msg);
        $order->save();
        return $this;
    }
}