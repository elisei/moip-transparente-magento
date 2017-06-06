<?php
class MOIP_Transparente_Adminhtml_StatusmoipController extends  Mage_Adminhtml_Controller_Action {

   
    protected function _isAllowed()
    {
        $action = strtolower($this->getRequest()->getActionName());
        $aclResource = 'sales/order/actions/setstate';
        return Mage::getSingleton('admin/session')->isAllowed($aclResource);
    }

    public function setstateAction()
    {
        $orderIds = $this->getRequest()->getPost('order_ids', array());
        $countCancelOrder = 0;
        $countNonCancelOrder = 0;
        foreach ($orderIds as $orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);
            $method = $order->getPayment()->getMethodInstance()->getCode();
            if ($method == "moip_cc" || $method == "moip_boleto" || $method == "moip_tef" ) {
                $this->getStateInMoip($order);
            }
        }
        $this->_redirect('adminhtml/sales_order/index/');
    }

    public function getStatusByORD($order){

		$state = $order->getState();
	   	$order_id = $order->getIncrementId();
	   	$order_real_id = $order->getId();
	   	$method = $order->getPayment()->getMethodInstance()->getCode();
	   	
		$model = Mage::getModel('transparente/write');
		$result = $model->load($order->getId(), 'mage_pay');

    	$moip_ord = $result->getMoipOrder();

    	if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
	          	$url = "https://sandbox.moip.com.br/v2/orders/ORD-{$moip_ord}";
	        	$oauth = trim(Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev'));
                $header = "Authorization: OAuth {$oauth}";
		    } else {
	        	$url = "https://api.moip.com.br/v2/orders/ORD-{$moip_ord}";
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

	         	if($moip_ord){
	                if($response_decode['status'] == "PAID"){
	                	if($state == Mage_Sales_Model_Order::STATE_NEW){
                			$this->_getSession()->addSuccess($this->__('O status do pedido %s será atualizado para autorizado.', $order_id));
                			$change_status = 1;
                		} 	elseif($state == Mage_Sales_Model_Order::STATE_HOLDED){ 
                				$this->_getSession()->addSuccess($this->__('O status do pedido %s será atualizado para autorizado.', $order_id));
                				$change_status = 1;
                		}	elseif($state == Mage_Sales_Model_Order::STATE_PROCESSING){
	                			$this->_getSession()->addNotice($this->__('O status do pedido %s já está atualizado.', $order_id));
	                			$change_status = 0;
	                	}	elseif($state == Mage_Sales_Model_Order::STATE_COMPLETE){
	                			$this->_getSession()->addNotice($this->__('O status do pedido %s já está atualizado.', $order_id));
	                			$change_status = 0;
	                	}	elseif($state == Mage_Sales_Model_Order::STATE_CLOSED){
	                			$this->_getSession()->addNotice($this->__('O status do pedido %s já está atualizado.', $order_id));
	                			$change_status = 0;
	                	}	elseif($state == Mage_Sales_Model_Order::STATE_CANCELED) {
	                		$this->_getSession()->addError($this->__('O pedido %s se encontra como cancelado em sua loja, no entanto ela está aprovada do moip, será necessário realizar ação manual de reorder.', $order_id));
	                		$change_status = 0;
	                	}
	                	else {
	                		$upOrder = $this->iniciaPagamento($order);
	                		$this->getStatusByORD($order);
	                		
	                	}

	                	if($change_status == 1){
	                		$upOrder = $this->autorizaPagamento($order);
	                	}
	                } elseif ($response_decode['status'] == "NOT_PAID") {

	                	if($state == Mage_Sales_Model_Order::STATE_NEW){
                			$this->_getSession()->addSuccess($this->__('O status do pedido %s será atualizado para cancelado.', $order_id));
                			$change_status = 1;
                		} 	elseif($state == Mage_Sales_Model_Order::STATE_HOLDED){ 
                				$this->_getSession()->addSuccess($this->__('O status do pedido %s será atualizado para cancelado.', $order_id));
                				$change_status = 1;
                		}	elseif($state == Mage_Sales_Model_Order::STATE_PROCESSING){
	                			$this->_getSession()->addError($this->__('O status do pedido %s em sua loja consta como PAGO, no entanto no Moip ainda se encontra CANCELADA', $order_id));
	                			$change_status =0;
	                	}	elseif($state == Mage_Sales_Model_Order::STATE_COMPLETE){
	                			$this->_getSession()->addError($this->__('O status do pedido %s em sua loja consta como PAGO, no entanto no Moip ainda se encontra CANCELADA', $order_id));
	                			$change_status =0;
	                	}	elseif($state == Mage_Sales_Model_Order::STATE_CLOSED){
	                			$this->_getSession()->addError($this->__('O status do pedido %s em sua loja consta como PAGO, no entanto no Moip ainda se encontra CANCELADA', $order_id));
	                			$change_status =0;
	                	}	elseif($state == Mage_Sales_Model_Order::STATE_CANCELED) {
	                			$this->_getSession()->addNotice($this->__('O status do pedido %s já está atualizado.', $order_id));
	                			$change_status =0;
						}	else {
	                			$upOrder = $this->iniciaPagamento($order);
	                			$this->getStatusByORD($order);
	                	}
	                	if($change_status == 1){
	                		$upOrder = $this->cancelaPagamento($order);
	                	}

	                } elseif ($response_decode['status'] == 'WAITING') {

	                	if($state == Mage_Sales_Model_Order::STATE_NEW){
                			$this->_getSession()->addSuccess($this->__('O status do pedido %s será atualizado para em espera.', $order_id));
                			$change_status =1;
                		} 	elseif($state == Mage_Sales_Model_Order::STATE_HOLDED){ 
                				$this->_getSession()->addNotice($this->__('O status do pedido %s já está atualizado.', $order_id));
                				$change_status =0;
                		}	elseif($state == Mage_Sales_Model_Order::STATE_PROCESSING){
	                			$this->_getSession()->addError($this->__('O status do pedido %s em sua loja consta já como PAGO, no entanto no Moip ainda se encontra EM ESPERA', $order_id));
	                			$change_status =0;
	                	}	elseif($state == Mage_Sales_Model_Order::STATE_COMPLETE){
	                			$this->_getSession()->addError($this->__('O status do pedido %s em sua loja consta já como PAGO, no entanto no Moip ainda se encontra EM ESPERA', $order_id));
	                			$change_status =0;
	                	}	elseif($state == Mage_Sales_Model_Order::STATE_CLOSED){
	                			$this->_getSession()->addError($this->__('O status do pedido %s em sua loja consta já como PAGO, no entanto no Moip ainda se encontra EM ESPERA', $order_id));
	                			$change_status =0;
	                	}	elseif($state == Mage_Sales_Model_Order::STATE_CANCELED) {
	                			$this->_getSession()->addError($this->__('O status do pedido %s em sua loja consta já como CANCELADO, no entanto no Moip ainda se encontra EM ESPERA', $order_id));
	                			$change_status =0;
						}	else {
	                			$upOrder = $this->iniciaPagamento($order);
	                			$this->getStatusByORD($order);
	                	}
	                	if($change_status == 1){
	                		$upOrder = $this->iniciaPagamento($order);
	                	}
	                	
	                } else {
	        			$this->_getSession()->addError($this->__('Error não foi possível analisar o status do pedido %s, por favor verefique no link: https://conta.moip.com.br/orders/ORD-%s.', $order_id, $moip_ord));
	                }
	            } else {
	            	$this->_getSession()->addError($this->__('Error não foi possível localizar o pedido %s, no banco de dados.', $order_id));
	            }

    }
    public function getStateInMoip($order){
		
		$state = $order->getState();
	   	$order_id = $order->getIncrementId();
	   	$order_real_id = $order->getId();
	   	$method = $order->getPayment()->getMethodInstance()->getCode();
	   	if($method == "moip_boleto"){
			 $onhold = $this->getStandard()->getConfigData('order_status_holded_boleto');
		} elseif ($method == "moip_cc"){
			$onhold = $this->getStandard()->getConfigData('order_status_holded');
		} elseif($method == "moip_tef"){
			$onhold = $this->getStandard()->getConfigData('order_status_holded_tef');
		} else {
			$onhold = $this->getStandard()->getConfigData('order_status_holded');
		}
		$standard = $this->getStandard();
		$model = Mage::getModel('transparente/write');
		$result = $model->load($order->getId(), 'mage_pay');

    	$moip_pay = $result->getMoipPay();

    	if($moip_pay){
    		if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
	          	$url = "https://sandbox.moip.com.br/v2/payments/{$moip_pay}";
	        	$oauth = trim(Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev'));
                $header = "Authorization: OAuth {$oauth}";
		    } else {
	        	$url = "https://api.moip.com.br/v2/payments/{$moip_pay}";
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

	         		
	                if($response_decode['status'] == "AUTHORIZED"){
	                	if($state == Mage_Sales_Model_Order::STATE_NEW){
                			$this->_getSession()->addSuccess($this->__('O status do pedido %s será atualizado para autorizado.', $order_id));
                			$change_status = 1;
                		} 	elseif($state == Mage_Sales_Model_Order::STATE_HOLDED){ 
                				$this->_getSession()->addSuccess($this->__('O status do pedido %s será atualizado para autorizado.', $order_id));
                				$change_status = 1;
                		}	elseif($state == Mage_Sales_Model_Order::STATE_PROCESSING){
	                			$this->_getSession()->addNotice($this->__('O status do pedido %s já está atualizado.', $order_id));
	                			$change_status = 0;
	                	}	elseif($state == Mage_Sales_Model_Order::STATE_COMPLETE){
	                			$this->_getSession()->addNotice($this->__('O status do pedido %s já está atualizado.', $order_id));
	                			$change_status = 0;
	                	}	elseif($state == Mage_Sales_Model_Order::STATE_CLOSED){
	                			$this->_getSession()->addNotice($this->__('O status do pedido %s já está atualizado.', $order_id));
	                			$change_status = 0;
	                	}	elseif($state == Mage_Sales_Model_Order::STATE_CANCELED) {
	                		$this->getStatusByORD($order);
	                		$change_status = 0;
	                	}
	                	else {
	                		$this->_getSession()->addError($this->__('O status do pedido %s não está correto, verificar manualmente junto ao seu painel Moip.', $order_id));
	                	}

	                	if($change_status == 1){
	                		$upOrder = $this->autorizaPagamento($order);
	                	}
	                } elseif ($response_decode['status'] == "CANCELLED") {

	                	if($state == Mage_Sales_Model_Order::STATE_NEW){
                			$this->_getSession()->addSuccess($this->__('O status do pedido %s será atualizado para cancelado.', $order_id));
                			$change_status = 1;
                		} 	elseif($state == Mage_Sales_Model_Order::STATE_HOLDED){ 
                				$this->_getSession()->addSuccess($this->__('O status do pedido %s será atualizado para cancelado.', $order_id));
                				$change_status = 1;
                		}	elseif($state == Mage_Sales_Model_Order::STATE_PROCESSING){
	                			$this->_getSession()->addError($this->__('O status do pedido %s em sua loja consta como PAGO, no entanto no Moip ainda se encontra CANCELADA', $order_id));
	                			$change_status =0;
	                	}	elseif($state == Mage_Sales_Model_Order::STATE_COMPLETE){
	                			$this->_getSession()->addError($this->__('O status do pedido %s em sua loja consta como PAGO, no entanto no Moip ainda se encontra CANCELADA', $order_id));
	                			$change_status =0;
	                	}	elseif($state == Mage_Sales_Model_Order::STATE_CLOSED){
	                			$this->_getSession()->addError($this->__('O status do pedido %s em sua loja consta como PAGO, no entanto no Moip ainda se encontra CANCELADA', $order_id));
	                			$change_status =0;
	                	}	elseif($state == Mage_Sales_Model_Order::STATE_CANCELED) {
	                			$this->_getSession()->addNotice($this->__('O status do pedido %s já está atualizado.', $order_id));
	                			$change_status =0;
						}	else {
	                			$this->_getSession()->addError($this->__('O status não está correto, verificar manualmente junto ao seu painel Moip.', $order_id));
	                	}
	                	if($change_status == 1){
	                		$upOrder = $this->cancelaPagamento($order);
	                	}

	                } elseif ($response_decode['status'] == 'WAITING') {

	                	if($state == Mage_Sales_Model_Order::STATE_NEW){
                			$this->_getSession()->addSuccess($this->__('O status do pedido %s será atualizado para em espera.', $order_id));
                			$change_status =1;
                		} 	elseif($state == Mage_Sales_Model_Order::STATE_HOLDED){ 
                				$this->_getSession()->addNotice($this->__('O status do pedido %s já está atualizado.', $order_id));
                				$change_status =0;
                		}	elseif($state == Mage_Sales_Model_Order::STATE_PROCESSING){
	                			$this->_getSession()->addError($this->__('O status do pedido %s em sua loja consta já como PAGO, no entanto no Moip ainda se encontra EM ESPERA', $order_id));
	                			$change_status =0;
	                	}	elseif($state == Mage_Sales_Model_Order::STATE_COMPLETE){
	                			$this->_getSession()->addError($this->__('O status do pedido %s em sua loja consta já como PAGO, no entanto no Moip ainda se encontra EM ESPERA', $order_id));
	                			$change_status =0;
	                	}	elseif($state == Mage_Sales_Model_Order::STATE_CLOSED){
	                			$this->_getSession()->addError($this->__('O status do pedido %s em sua loja consta já como PAGO, no entanto no Moip ainda se encontra EM ESPERA', $order_id));
	                			$change_status =0;
	                	}	elseif($state == Mage_Sales_Model_Order::STATE_CANCELED) {
	                			$this->_getSession()->addError($this->__('O status do pedido %s em sua loja consta já como CANCELADO, no entanto no Moip ainda se encontra EM ESPERA', $order_id));
	                			$change_status =0;
						}	else {
	                			$this->getStatusByORD($order);
	                	}
	                	if($change_status == 1){
	                		$upOrder = $this->iniciaPagamento($order);
	                	}
	                	
	                } else {
	        			$this->getStatusByORD($order);
	                }
	       
    	} else {
    		$this->getStatusByORD($order);
    	}
    		
            
        
    	
    }
    public function autorizaPagamento($order){
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
	 }

	 public function iniciaPagamento($order, $onhold){

	 	$state = Mage_Sales_Model_Order::STATE_HOLDED;
		$status = $onhold;
		$comment = "Pagamento Iniciado, aguardando confirmação automática.";
		$update = $this->updateInOrder($order, $state, $status, $comment);
	 }

	 public function cancelaPagamento($order){
	 	
	 	if($order->canUnhold()) {
			$order->unhold()->save();
		}
		$order->cancel()->save();
		$state = Mage_Sales_Model_Order::STATE_CANCELED;
		$link = Mage::getUrl('sales/order/reorder/');
        $link = $link.'order_id/'.$order->getEntityId();
		$comment = "Para refazer o pagamento acesse o link: ".$link;
		$status = 'canceled';
		$order->setState($state, $status, $comment, $notified = true, $includeComment = true);
		$order->save();
		$order->sendOrderUpdateEmail(true, $comment);
		
	 }

	 public function updateInOrder($order, $state, $status, $comment){
	 	$order->setState($state, $status, $comment, $notified = true, $includeComment = true);
		$order->save();
		$order->sendOrderUpdateEmail(true, $comment);
	 }

    public function getApi()
    {
        return Mage::getModel('transparente/api');
    }

    public function getStandard() {
        return Mage::getSingleton('transparente/standard');
    }
}