<?php
/**
 * Transparente - Transparente Payment Module
 *
 * @title      Magento -> Custom Payment Module for Transparente (Brazil)
 * @category   Payment Gateway
 * @package    MOIP_Transparente
 * @author     Moip solucoes web ldta
 * @copyright  Copyright (c) 2010 Transparente Pagamentos S/A
 * @license    Autorizado o uso por tempo indeterminado
 */
class MOIP_Transparente_StandardController extends Mage_Core_Controller_Front_Action {
	
	public function getStandard() {
		return Mage::getSingleton('transparente/standard');
	}

	public function _prepareLayout()
	{
		parent::_prepareLayout();
	}

	protected function _expireAjax() {
		if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
			$this->getResponse()->setHeader('HTTP/1.1', '403 Session Expired');
			exit;
		}
	}

	public function set404(){
		$this->getResponse()->setHeader('HTTP/1.1','404 Not Found');
	    $this->getResponse()->setHeader('Status','404 File not found');

	    $pageId = Mage::getStoreConfig(Mage_Cms_Helper_Page::XML_PATH_NO_ROUTE_PAGE);
	    if (!Mage::helper('cms/page')->renderPage($this, $pageId)) {
	        $this->_forward('defaultNoRoute');
	    }
	}

	public function getApi()
    {
        $api = Mage::getModel('transparente/api');
        return $api;
    }

    public function testAction(){
    	$api 			= $this->getApi();
    	$params 		= $this->getRequest()->getParams();
		$json_moip 		= $this->getRequest()->getRawBody();
		$json_moip = json_decode($json_moip);

    	 if(isset($json_moip->resource->order->id)){
            //recupera infos para ORD.*
            $moip_order = $json_moip->resource->order->id;
            $status_moip = $json_moip->resource->order->status;
        } elseif(isset($json_moip->resource->payment)){
            //recupera infos para PAY.*
            $moip_order = $json_moip->resource->payment->_links->order->title;
            $amount = $json_moip->resource->payment->amount->total/100;
            $status_moip = $json_moip->resource->payment->status;
            if(isset($json_moip->resource->payment->cancellationDetails)){
				$details_cancel 	= $json_moip->resource->payment->cancellationDetails->description;	
			} else{
				$details_cancel 	= "Indefinido";
			}
        } 

        
        $order = Mage::getModel('sales/order')->load($moip_order, 'ext_order_id');
        if($order->getId()){
        	Mage::getModel('transparente/email_cancel')->sendEmail($order, $details_cancel);
        }
        


    }
    
    public function successAction(){
    	error_reporting(E_ALL);
		ini_set("display_errors",1);
		/*$localeCode = Mage::getStoreConfig('payment/moip_transparente_standard/moip_cancel');
		$emailTemplate = Mage::getModel('core/email_template')->loadByCode($localeCode);

		var_dump($localeCode );
		var_dump($emailTemplate->getTemplateSubject());
		die();*/

		$api 			= $this->getApi();
		
		if(!$this->getRequest()->getRawBody()){
			$api->generateLog("Não foi possiviel ler o body", 'MOIP_WebHooksError.log');
			return $this->set404();
		}
		
		$params 		= $this->getRequest()->getParams();
		$json_moip 		= $this->getRequest()->getRawBody();
        $autorization 	= $this->getRequest()->getHeader('Authorization');  

        $api->generateLog("autorizationAction: ".$json_moip, 'MOIP_WebHooks.log');
        

        if($params['validacao'] == $this->getStandard()->getConfigData('validador_retorno')){

			$json_moip = json_decode($json_moip);

			$newMethodForOrder = $this->newGetOrder($json_moip);
			if($newMethodForOrder != 1 && $newMethodForOrder == "error_no_new_order"){



				$transation = $this->getTransationMoip($json_moip);


				if($transation->getMoipResponse() != 1){


					$processing = $this->processOrder($transation, $json_moip); 
					

					if($processing){
						$api->generateLog("processing", 'MOIP_WebHooks.log');
						echo "Sucesso";
					} else {
						return $this->set404();
					}
					
				} else {
					$api->generateLog("Evento em duplicidade autorizationAction", 'MOIP_WebHooksError.log');
					echo "duplicado";
					return;
				}
			} else {
				echo "Sucesso";
				return $this;
			}

		} else {
			$api->generateLog("Validação de comunicação INVÁLIDA: ".$params, 'MOIP_WebHooksError.log');
			return $this->set404();
		}
    }

    public function newGetOrder($json_moip){
        $api            = $this->getApi();
        
        if(isset($json_moip->resource->order->id)){
            //recupera infos para ORD.*
            $moip_order = $json_moip->resource->order->id;
            $status_moip = $json_moip->resource->order->status;
        } elseif(isset($json_moip->resource->payment)){
            //recupera infos para PAY.*
            $moip_order = $json_moip->resource->payment->_links->order->title;
            $amount = $json_moip->resource->payment->amount->total/100;
            $status_moip = $json_moip->resource->payment->status;
            if(isset($json_moip->resource->payment->cancellationDetails)){
				$details_cancel 	= $json_moip->resource->payment->cancellationDetails->description;	
			} else{
				$details_cancel 	= "Indefinido";
			}
        } else {
            $api->generateLog("MOIP ORDER não localizada: ", 'MOIP_WebHooksError.log');
            return $this->set404();
        }
        $order = Mage::getModel('sales/order')->load($moip_order, 'ext_order_id');
        $payment = $order->getPayment();

        if($status_moip == "AUTHORIZED" || $status_moip == "PAID"){
            $test = $payment->getMethodInstance()->authorize($payment, $amount);
             try {
                return 1;
             } catch (Exception $e) {
                $api->generateLog("MOIP {$moip_order} não foi processada erro: ".$e, 'MOIP_WebHooksError.log');
                return "error_no_new_order";
             }
        } elseif ($status_moip == "CANCELLED" || $status_moip == "NOT_PAID") {

           $transactionAuth = $payment->getMethodInstance()->cancel($payment);
           
           if($transactionAuth){
           		$order->cancel()->save();
           		Mage::getModel('transparente/email_cancel')->sendEmail($order, $details_cancel);
           			$translate_details = Mage::helper('transparente')->__($details_cancel);
           			$msg = Mage::helper('transparente')->__('Email de cancelamento enviado ao cliente. Motivo real: %s, motivo exibido ao cliente: %s', $details_cancel, $translate_details);
           			$order->addStatusHistoryComment($msg);
					$order->save();
           		try {
           			
	                return 1;
	             } catch (Exception $e) {
	                $api->generateLog("MOIP {$moip_order} não foi processada erro: ".$e, 'MOIP_WebHooksError.log');
	                return "error_no_new_order";
	             }
           } else {

           } return !1;
           	
             
        } else {
            return !1;
        }
    }

	

	public function getTransationMoip($json_moip){
		$api 			= $this->getApi();
		
		if(isset($json_moip->resource->order->id)){
			$moip_order = (string)$json_moip->resource->order->id;
			
		} elseif(isset($json_moip->resource->payment)){
			$moip_order = (string)$json_moip->resource->payment->_links->order->title;
		} else {
			$api->generateLog("MOIP ORDER não localizada: ", 'MOIP_WebHooksError.log');
			return $this->set404();
		}

		$result = Mage::getModel('transparente/transparente')->load(str_replace("ORD-", "",$moip_order), 'moip_order');
		
		if($result->getData()){
			if($result->getMoipResponse() != 1){
				
				return $result;
			} else {
				$api->generateLog("Evento em duplicidade getTransationMoip", 'MOIP_WebHooksError.log');
				return $result;
			}

		} else {
			$api->generateLog($json_moip, 'MOIP_WebHooksError.log');
		    return $this->set404();
		}

	}

	public function processOrder($transation, $json_moip){

		$transation->setMoipResponse(1)->save();

		$api 			= $this->getApi();
		$mage_order 	= $transation->getMagePay();

		$order 			= Mage::getModel('sales/order')->load($mage_order);
		
		if(isset($json_moip->resource->order->id)){
			$status_moip = (string)$json_moip->resource->order->status;
		} elseif(isset($json_moip->resource->payment)){
			$status_moip = (string)$json_moip->resource->payment->status;
		} else {
			return !1;
		}

		$method 		= $transation->getFormaPagamento();


		if($method == "moip_boleto"){
			$details_cancel 	= "Prazo para pagamento excedido";
		} elseif ($method == "moip_cc"){
			if(isset($json_moip->resource->payment->cancellationDetails)){
				$details_cancel 	= $json_moip->resource->payment->cancellationDetails->description;	
			} else{
				$details_cancel 	= "Indefinido";
			}
		} elseif($method == "moip_tef"){
			$details_cancel 	= "Prazo para pagamento excedido";
		} elseif($method == "moip_transparente_standard"){
			$details_cancel 	= "Prazo para pagamento excedido";
		} else {
			$api->generateLog("Metodo de pagamento inexistente: ".$method, 'MOIP_WebHooksError.log');
			return $this->set404();
		}


		if($order->getId()){

			$order_state = $order->getState();


			if($order_state == Mage_Sales_Model_Order::STATE_NEW &&  $this->initState('type_status_init') !=  "not"){
				$this->set404();
			}

			if(		$status_moip == "AUTHORIZED" || $status_moip == "PAID" && 
					($order_state != Mage_Sales_Model_Order::STATE_COMPLETE && $order_state != Mage_Sales_Model_Order::STATE_PROCESSING && $order_state != Mage_Sales_Model_Order::STATE_CLOSED) ){

				if($this->initState('type_status_init') ==  "onhold") {
					if($order->canUnhold()) {
						$order->unhold()->save();
						try {
							$upOrder = $this->autorizaPagamento($order);
						} catch (Mage_Core_Exception $e) {
							$transation->setMoipResponse(0)->save();
							$api->generateLog("Não atualizou - ".$e->getMessage(), 'MOIP_WebHooksError.log');
				            $this->_fault('status_not_changed', $e->getMessage());
				            return !1;
				        }
					} else {
						$api->generateLog("Não atualizou - A order não pode ser liberada...", 'MOIP_WebHooksError.log');
					}
				} else {
						$upOrder = $this->autorizaPagamento($order);
				}
				
				


				//verifica se foi aplicada a autorização
				if($upOrder->getState() == Mage_Sales_Model_Order::STATE_PROCESSING) {
					$api->generateLog("Order: ". $upOrder->getIncrementId() . 'state alterado para  '.$upOrder->getState(). ' status alterado para  '.$upOrder->getStatus(), 'MOIP_WebHooks.log');

				 	return 1;
				} else {
					$transation->setMoipResponse(0)->save();
					$api->generateLog("Order: ". $upOrder->getIncrementId() ." ficou com state ". $upOrder->getState()." e status ". $upOrder->getStatus(), 'MOIP_WebHooksError.log');
				 	return !1;
				}



			} elseif($status_moip == "CANCELLED" || $status_moip == "NOT_PAID" && $order_state != Mage_Sales_Model_Order::STATE_COMPLETE && $order_state != Mage_Sales_Model_Order::STATE_PROCESSING && $order_state != Mage_Sales_Model_Order::STATE_CLOSED && $order_state != Mage_Sales_Model_Order::STATE_CANCELED){

				//solicita o cancelamento
				if($this->initState('type_status_init') ==  "onhold") {


					if($order->canUnhold()) {
						$order->unhold()->save();
							try {
								$upOrder = $this->cancelaPagamento($order,$details_cancel);
							} catch (Mage_Core_Exception $e) {
								$transation->setMoipResponse(0)->save();
								$api->generateLog("Não atualizou - ".$e->getMessage(), 'MOIP_WebHooksError.log');
					            $this->_fault('status_not_changed', $e->getMessage());
					            return $this->set404();
			        		}
			        } else {
			        	$api->generateLog("Não atualizou - A order não pode ser liberada...", 'MOIP_WebHooksError.log');
			        }

				} else {
					$upOrder = $this->cancelaPagamento($order,$details_cancel);
				}

				//verifica se foi aplicado
				if($upOrder->getState() == Mage_Sales_Model_Order::STATE_CANCELED) {
					$api->generateLog("Order: ". $upOrder->getIncrementId() ." ficou com state ". $upOrder->getState()." e status ". $upOrder->getStatus(), 'MOIP_WebHooks.log');
				 	return 1;

				} else {
					$transation->setMoipResponse(0)->save();
					$api->generateLog("Order: ". $upOrder->getIncrementId() ." ficou com state ". $upOrder->getState()." e status ". $upOrder->getStatus(), 'MOIP_WebHooksError.log');
				 	return !1;
				}

				
				 
			} elseif($status_moip == "REFUNDED"){
				return $this->refundPagamento($order, $refundToStoreCreditAmount, $comment);
			} 

		} else {
			$api->generateLog("Order não existe", 'MOIP_WebHooksError.log');
			return !1;
		}

	}



	

	
	public function cancelAction() {
		$session = Mage::getSingleton('checkout/session');
		$session->setQuoteId($session->getTransparenteStandardQuoteId(true));

		if ($session->getLastRealOrderId()) {
			$order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
			if ($order->getId()) {
				$order->cancel()->save();
				$state = Mage_Sales_Model_Order::STATE_CANCELED;
				$status = 'canceled';
				$comment = $session->getMoipError();
				$order->setState($state, $status, $comment, $notified = true, $includeComment = true);
				$order->save();
			}
		}
		$this->_redirect('checkout/onepage/failure');
	}


	public function autorizaPagamento($order){
			
			$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)
			        ->setStatus(Mage_Sales_Model_Order::STATE_PROCESSING)
			        ->save();
			$invoice = $order->prepareInvoice();
			if ($this->getStandard()->canCapture())
			{
					$invoice->register()->capture();
			}
			Mage::getModel('core/resource_transaction')->addObject($invoice)->addObject($invoice->getOrder())->save();
			$invoice->sendEmail();
			$invoice->setEmailSent(true);
			$invoice->save();
			return $order;
		

	 }

	
	public function cancelaPagamento($order, $details = "Indefinido"){
			$order->cancel()->save();
			/*$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true)
			        ->setStatus(Mage_Sales_Model_Order::STATE_CANCELED)
			        ->save();*/
			$state = Mage_Sales_Model_Order::STATE_CANCELED;
			$storeId = $order->getStoreId();
			$link_store = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
			$link = $link_store.'sales/order/reorder/order_id/'.$order->getEntityId();
			$comment = "Motivo: ".Mage::helper('transparente')->__($details)." Para refazer o pagamento acesse o link: ".$link;
			$status = 'canceled';
			$order->setState($state, $status, $comment, $notified = true, $includeComment = true);
			$order->sendOrderUpdateEmail(true, $comment);
			$order->save();

			return $order;
	}

	public function refundPagamento($order, $refundToStoreCreditAmount, $comment)
	{
				
        if (!$order->getId()) {
            $this->_fault('order_not_exists');
        }
        if (!$order->canCreditmemo()) {
            $this->_fault('cannot_create_creditmemo');
        }
        $data = array();
 
         
        $service = Mage::getModel('sales/service_order', $order);
        
        $creditmemo = $service->prepareCreditmemo($data);
 
        
        if ($refundToStoreCreditAmount) {
            if ($order->getCustomerIsGuest()) {
                $this->_fault('cannot_refund_to_storecredit');
            }
            $refundToStoreCreditAmount = max(
                0,
                min($creditmemo->getBaseCustomerBalanceReturnMax(), $refundToStoreCreditAmount)
            );
            if ($refundToStoreCreditAmount) {
                $refundToStoreCreditAmount = $creditmemo->getStore()->roundPrice($refundToStoreCreditAmount);
                $creditmemo->setBaseCustomerBalanceTotalRefunded($refundToStoreCreditAmount);
                $refundToStoreCreditAmount = $creditmemo->getStore()->roundPrice(
                    $refundToStoreCreditAmount*$order->getStoreToOrderRate()
                );
                $creditmemo->setBsCustomerBalTotalRefunded($refundToStoreCreditAmount);
                $creditmemo->setCustomerBalanceRefundFlag(true);
            }
        }
        $creditmemo->setPaymentRefundDisallowed(true)->register();
        $creditmemo->addComment($comment, 1);
        try {
            Mage::getModel('core/resource_transaction')
                ->addObject($creditmemo)
                ->addObject($order)
                ->save();
            
            $creditmemo->sendEmail(true, $comment);
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }
        echo $creditmemo->getIncrementId();
	}
	
	public function initState($value){
        return Mage::getSingleton('transparente/standard')->getConfigData($value);
    }
	 
	

}