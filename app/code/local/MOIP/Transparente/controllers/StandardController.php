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

	public function successAction() {
		error_reporting(E_ALL);
		ini_set("display_errors",1);
		
		$api 			= $this->getApi();
		$body 			= $this->getRequest()->getRawBody();
		$params 		= $this->getRequest()->getParams();
		$json_moip 		= $this->getRequest()->getRawBody();
        $autorization 	= $this->getRequest()->getHeader('Authorization');   
        $api->generateLog("successAction: ".$json_moip, 'MOIP_WebHooks.log');
		if(!$this->getRequest()->getRawBody()){
			$api->generateLog("Não foi possiviel ler o body", 'MOIP_WebHooksError.log');
			return $this->set404();
		}
		if($params['validacao'] == $this->getStandard()->getConfigData('validador_retorno')){
		
			$json_moip = json_decode($json_moip);
			return $this->getTransationMoip($json_moip);
		}
		else {
			$api->generateLog("Sem validaçao: ".$params, 'MOIP_WebHooksError.log');
			$api->generateLog($this->getStandard()->getConfigData('validador_retorno'), 'MOIP_WebHooksError.log');
			$api->generateLog("Json: ".$json_moip, 'MOIP_WebHooksError.log');
			return $this->set404();
			
		}
	}

	public function getTransationMoip($json_moip){
		$api 			= $this->getApi();
		
		if(isset($json_moip->resource->payment)){
			$moip_order = (string)$json_moip->resource->payment->_links->order->title;
			$status_moip = (string)$json_moip->resource->payment->status;
			
		} else {
			
			$refundToStoreCreditAmount = null;
			$moip_order = $json_moip->resource->order->id;
			$status_moip = (string)$json_moip->resource->order->status;
			if (isset($json_moip->resource->order->refunds)) {
				$refunds = $json_moip->resource->order->refunds;
					foreach ($refunds as $key => $value) {
						$refundToStoreCreditAmount = $value->amount->total;
					}
				$comment = "Reembolso para o Pedido: ".$moip_order;
				$refundToStoreCreditAmount = $refundToStoreCreditAmount/100;
			} else {
				
				return $this->set404();
				
			}
		}
		
		
		$result_table 	= $this->findOrderMage($moip_order);
		$api->generateLog($result_table, 'MOIP_WebHooks.log');
		if(!$result_table){
			$api->generateLog("Sem resultado na tabela moip: ".$json_moip, 'MOIP_WebHooksError.log');
			return $this->set404();
		}
		$mage_order 	= $result_table->getMagePay();
		$method 		= $result_table->getFormaPagamento();
		$api->generateLog($mage_order, 'MOIP_WebHooks.log');
		$api->generateLog($method, 'MOIP_WebHooks.log');
		if($method == "moip_boleto"){
			$details_cancel 	= "Prazo para pagamento excedido";
		} 
		elseif ($method == "moip_cc"){
			if(isset($json_moip->resource->payment->cancellationDetails)){
				$details_cancel 	= $json_moip->resource->payment->cancellationDetails->description;	
			} else{
				$details_cancel 	= "Indefinido";
			}

		} elseif($method == "moip_tef"){
			$details_cancel 	= "Prazo para pagamento excedido";
		} else {
			$api->generateLog("Metodo de pagamento inexistente: ".$json_moip, 'MOIP_WebHooksError.log');
			return $this->set404();
		}
		
		$order = Mage::getModel('sales/order')->load($mage_order);

		if($order->getId()){

			$order_status 	= $order->getStatus();
			
			if($order_status == Mage_Sales_Model_Order::STATE_NEW){
				sleep(1);
				$order_status 	= $order->getStatus();
			}

			elseif($status_moip == "AUTHORIZED" && $order_status != Mage_Sales_Model_Order::STATE_COMPLETE && $order_status != Mage_Sales_Model_Order::STATE_PROCESSING && $order_status != Mage_Sales_Model_Order::STATE_CLOSED){
				$upOrder = $this->autorizaPagamento($order);
				return $upOrder;
			} elseif($status_moip == "CANCELLED" && $order_status != Mage_Sales_Model_Order::STATE_COMPLETE && $order_status != Mage_Sales_Model_Order::STATE_PROCESSING && $order_status != Mage_Sales_Model_Order::STATE_CLOSED && $order_status != Mage_Sales_Model_Order::STATE_CANCELED){
				 $upOrder = $this->cancelaPagamento($order,$details_cancel);
				 return $upOrder;
			} elseif($status_moip == "REFUNDED"){
				return $this->refundPagamento($order, $refundToStoreCreditAmount, $comment);
			} else{

				
				$api->generateLog("Status de pagamento inexistente: ".$json_moip, 'MOIP_WebHooksError.log');
				$api->generateLog("Status de moip: ".$status_moip, 'MOIP_WebHooksError.log');
				$api->generateLog($order_status, 'MOIP_WebHooksError.log');
				return $this->set404();
			}
		} else {
			$api->generateLog("Order inexistente: ".$json_moip, 'MOIP_WebHooksError.log');
			return $this->set404();
		}
	}

	public function findOrderMage($moip_ord){
		$result = Mage::getModel('transparente/write')->load(str_replace("ORD-", "",$moip_ord), 'moip_order');
		if($result->getMagePay()){
			return $result;	
		} else {
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
		$this->getResponse()->setHeader('HTTP/1.0','200',true);
	 }

	public function initState($value){
        return Mage::getSingleton('transparente/standard')->getConfigData($value);
    }
	 
	public function cancelaPagamento($order, $details){
	 	if($order->canUnhold()) {
			$order->unhold()->save();
		} 
		$order->cancel()->save();
		$state = Mage_Sales_Model_Order::STATE_CANCELED;
		$link = Mage::getUrl('sales/order/reorder/');
        $link = $link.'order_id/'.$order->getEntityId();
		$comment = "Motivo: ".Mage::helper('transparente')->__($details)." Para refazer o pagamento acesse o link: ".$link;
		$status = 'canceled';
		$order->setState($state, $status, $comment, $notified = true, $includeComment = true);
		$order->save();
		$order->sendOrderUpdateEmail(true, $comment);
		$this->getResponse()->setHeader('HTTP/1.0','200',true);
		return;
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
	

	

}