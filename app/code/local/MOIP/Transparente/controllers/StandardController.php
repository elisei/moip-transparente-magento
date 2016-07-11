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

	public function getApi()
    {
        $api = Mage::getModel('transparente/api');
        return $api;
    }

	public function redirectAction() {
		$session = Mage::getSingleton('checkout/session');
		$getSaltes = Mage::getModel('sales/order');
		$session->setCurrent_order($getSaltes->load($session->getLastOrderId()));
		Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($session->getLastOrderId())));
		$this->loadLayout();
		$this->renderLayout();
	}

	protected function _getItemQtys()
	{
	    $data = $this->getRequest()->getParam('invoice');
	    if (isset($data['items'])) {
	        $qtys = $data['items'];
	    } else {
	        $qtys = array();
	    }
	    return $qtys;
	}

	public function OauthAction() {
		$api = $this->getApi();
		$standard = $this->getStandard();
		$data = $this->getRequest()->getParams();
		$model = new Mage_Core_Model_Config();
		$api->generateLog($data['validacao'], 'MOIP_Oauth.log');
		
		$json_log = json_encode($data);
		$api->generateLog($json_log, 'MOIP_Oauth.log');
		if($data['validacao']== $standard->getConfigData('validador_retorno')){
			$store_id = $data['store_id'];

			if($data['store_id']){
				$store_code = $data['store_id'];
			} else {
				$store_code = 'default';
			}
			if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
				$oauth =  $this->getOauthAcess($data['code']);
				$oauth_decode = json_decode($oauth, true);
				$api->generateLog($oauth_decode['accessToken'], 'MOIP_Oauth.log');
				$model->saveConfig('payment/moip_transparente_standard/oauth_dev', $oauth_decode['accessToken'], $store_code, $store_id);
				$public_key = $this->getKeyPublic($oauth_decode['accessToken']);
				$model->saveConfig('payment/moip_transparente_standard/publickey_dev', $public_key, $store_code, $store_id);
			} else {
				$oauth =  $this->getOauthAcess($data['code']);
				$oauth_decode = json_decode($oauth, true);
				$api->generateLog($oauth_decode['accessToken'], 'MOIP_Oauth.log');
				$model->saveConfig('payment/moip_transparente_standard/oauth_prod', (string)$oauth_decode['accessToken'], 'default', 0);
				$public_key = $this->getKeyPublic($oauth_decode['accessToken']);
				$model->saveConfig('payment/moip_transparente_standard/publickey_prod', $public_key, $store_code, 0);
			}
			Mage::getSingleton('core/session')->addSuccess('Configuração Concluída, por favor realize o seu teste.');
			$url = "";
			Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl($url));
		} else {
			echo utf8_decode("ha ha ha... você não tem autorização para realizar pagamentos...");
		}

	}

	public function getOauthAcess($code) {
		$api = $this->getApi();
		$documento = 'Content-Type: application/json; charset=utf-8';
		$api->generateLog($code, 'MOIP_Oauth.log');

		 if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
	          $url = "https://sandbox.moip.com.br/oauth/accesstoken";
	        	$header = "Authorization: Basic " . base64_encode(MOIP_Transparente_Model_Api::TOKEN_TEST . ":" . MOIP_Transparente_Model_Api::KEY_TEST);
	        	$array_json = array(
		        	'appId' => 'APP-9MUFQ39Y4CQU', //Alterar aqui tb....
		        	'appSecret' => '26xa86dbc7mhdyqq2w69vscvhz47cri', //Alterar aqui tb....
					'redirectUri' => 'http://moip.o2ti.com/magento/redirect/',
					'grantType' => 'authorization_code',
					'code' => $code
	        	);
	        	$json = json_encode($array_json);
	      }
	          else {
	              $url = "https://api.moip.com.br/oauth/accesstoken";
			        $header = "Authorization: Basic " . base64_encode(MOIP_Transparente_Model_Api::TOKEN_PROD . ":" . MOIP_Transparente_Model_Api::KEY_PROD);
			        $array_json = array(
				        	'appId' => 'APP-AKYBMMVU1FL1', //Alterar aqui tb....
				        	'appSecret' => 'db9pavx8542khvsyn3s0tpxyu2gom2m', //Alterar aqui tb....
							'redirectUri' => 'http://moip.o2ti.com/magento/redirect/',
							'grantType' => 'authorization_code',
							'code' => $code
			        	);
			        	$json = json_encode($array_json);
	      }
	      $result = array();
	      $ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,$url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 6000);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array($header, $documento));
			curl_setopt($ch,CURLOPT_USERAGENT,'MoipMagento/2.0.0');

			$res = curl_exec($ch);
		 	curl_close($ch);
		 	$api->generateLog($res, 'MOIP_Oauth.log');
		 	
		return $res;
	}

	


	public function getKeyPublic($oauth) {
		$api = $this->getApi();
		$api->generateLog($oauth, 'MOIP_Oauth.log');
		$documento = 'Content-Type: application/json; charset=utf-8';
		
			if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
		            $url = "https://sandbox.moip.com.br/v2/keys/";
		           	$header = "Authorization: OAuth " . $oauth;
		    } else {
		            $url = "https://api.moip.com.br/v2/keys/";
		            $header = "Authorization: OAuth " . $oauth;
		    }
		    $result = array();
		    $ch = curl_init();
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		    curl_setopt($ch, CURLOPT_URL, $url);
		    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		    curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));
		    curl_setopt($ch,CURLOPT_USERAGENT,'MoipMagento/2.0.0');
		    $responseBody = curl_exec($ch);
		    curl_close($ch);
		    
		    $api->generateLog($responseBody, 'MOIP_Oauth.log');
		    $responseBody = json_decode($responseBody, true);
		    $public_key = $responseBody['keys']['encryption'];
		    $api->generateLog($public_key, 'MOIP_Oauth.log');
		    

		return $public_key;
	}

	 public function successAction() {
	 	error_reporting(E_ALL);
		ini_set("display_errors",1);

	 	$api = $this->getApi();
		$standard = $this->getStandard();
		$validacao = $this->getRequest()->getParams();
		$result = array('');
		$status_moip  = "";
		$moip_ord  = "";
		$json_moip = $this->getRequest()->getRawBody();
		if(!isset($validacao)){
			return; 
		}
		
		$json_moip = json_decode($json_moip);

		if(isset($json_moip->resource->payment)){

			$moip_ord = (string)$json_moip->resource->payment->_links->order->title;
			$status_moip = (string)$json_moip->resource->payment->status;

		} else {
			$refundToStoreCreditAmount = null;
			$moip_ord = $json_moip->resource->order->id;
			$status_moip = (string)$json_moip->resource->order->status;
			if (isset($json_moip->resource->order->refunds)) {
				
				$refunds = $json_moip->resource->order->refunds;
					foreach ($refunds as $key => $value) {
						$refundToStoreCreditAmount = $value->amount->total;
					}
				$comment = "Reembolso para o Pedido: ".$moip_ord;
				
				$refundToStoreCreditAmount = $refundToStoreCreditAmount/100;
				
				
				
			}

		}


		$model = Mage::getModel('transparente/write');
		$order_moip = str_replace("ORD-", "",$moip_ord);
		$result = $model->load($order_moip, 'moip_order');
		$mage_ord = $result->getMagePay();
		$method = $result->getFormaPagamento();

		if($method == "moip_boleto"){
			 $onhold = $standard->getConfigData('order_status_holded_boleto');
		} elseif ($method == "moip_cc"){
			$onhold = $standard->getConfigData('order_status_holded');
		} elseif($method == "moip_tef"){
			$onhold = $standard->getConfigData('order_status_holded_tef');
		} else {
			$onhold = $standard->getConfigData('order_status_holded');
		}
			
			

		
		if($validacao['validacao']== $standard->getConfigData('validador_retorno') && $status_moip != ""){
			
			$order = Mage::getModel('sales/order')->load($mage_ord);
			$order_status = $order->getStatus();
			$api->generateLog($json_moip, 'MOIP_WebHooks.log');
			$api->generateLog($status_moip, 'MOIP_WebHooks.log');
			$api->generateLog($order_moip, 'MOIP_WebHooks.log');
			$api->generateLog($mage_ord, 'MOIP_WebHooks.log');
			if($order->getId()){
				try {
					if($status_moip == "AUTHORIZED" && $order_status != "processing"){
						$paid = $standard->getConfigData('order_status_processing');

						$upOrder = $this->autorizaPagamento($order, $paid);
						return $upOrder;
					} elseif($status_moip == "WAITING" && $order_status == "pending") {
						$upOrder = $this->iniciaPagamento($order, $onhold);
						return $upOrder;

					} elseif($status_moip == "CANCELLED" && $order_status != "processing"){
						$details = $json_moip->resource->payment->cancellationDetails->description;
						
						 $upOrder = $this->cancelaPagamento($order,$details);
						 return $upOrder;
					} elseif($status_moip == "REFUNDED"){
						return $this->refundPagamento($order, $refundToStoreCreditAmount, $comment);
					} else{
						die();
					}
				} catch (Exception $order) {
	  				$api->generateLog($order, 'MOIP_WebHooksErro.log');
	  			}

  			} else {
  				Mage::throwException(Mage::helper('core')->__('Order não encontrada'));
  			}
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


	 public function autorizaPagamento($order, $paid){
	 	sleep(5);
	 	

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

	 public function iniciaPagamento($order, $onhold){

	 	$state = Mage_Sales_Model_Order::STATE_HOLDED;
		$status = $onhold;
		$comment = "Pagamento Iniciado, aguardando confirmação automática.";
		$update = $this->updateInOrder($order, $state, $status, $comment);
		return $update;
	 }

	 public function cancelaPagamento($order, $details){
	 	sleep(5);
	 	if($order->canUnhold()) {
			$order->unhold()->save();
		}
		$order->cancel()->save();
		$state = Mage_Sales_Model_Order::STATE_CANCELED;
		$link = Mage::getUrl('sales/order/reorder/');
        $link = $link.'order_id/'.$order->getEntityId();
		$comment = "Motivo: ".$details." Para refazer o pagamento acesse o link: ".$link;
		$status = 'canceled';
		$order->setState($state, $status, $comment, $notified = true, $includeComment = true);
		$order->save();
		$order->sendOrderUpdateEmail(true, $comment);
		return $update;
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
                // this field can be used by customer balance observer
                $creditmemo->setBsCustomerBalTotalRefunded($refundToStoreCreditAmount);
                // setting flag to make actual refund to customer balance after credit memo save
                $creditmemo->setCustomerBalanceRefundFlag(true);
            }
        }
        $creditmemo->setPaymentRefundDisallowed(true)->register();
        // add comment to creditmemo
        
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


	 public function updateInOrder($order, $state, $status, $comment){
	 	$order->setState($state, $status, $comment, $notified = true, $includeComment = true);
		$order->save();
		$order->sendOrderUpdateEmail(true, $comment);
		return true;
	 }


	public function buscaCepAction() {
		$data = $this->getRequest()->getParams();

		if ($data['meio'] == "cep") {
			
			$cep = $data['cep'];
			$cep = substr(preg_replace("/[^0-9]/", "", $cep) . '00000000', 0, 8);
			$url = "http://endereco.ecorreios.com.br/app/enderecoCep.php?cep={$cep}";

			$result = array();
		    $ch = curl_init();
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		    curl_setopt($ch, CURLOPT_URL, $url);
		    curl_setopt($ch,CURLOPT_USERAGENT,'MoipMagento/2.0.0');
		    $responseBody = curl_exec($ch);
		    curl_close($ch);


			if($responseBody){
				

				$endereco = Mage::helper('core')->jsonDecode($responseBody);

				/* 
				//Remover comentário para usar a versão em que o distrito federal está fora da ordem alfabética. Nesse caso lembre-se que precisa remover o switch de baixo.
				switch ($endereco['uf']) {
					case "AC":
						$endereco['ufid'] = 485;
						break;
					case "AL":
						$endereco['ufid'] = 486;
						break;
					case "AP":
						$endereco['ufid'] = 487;
						break;
					case "AM":
						$endereco['ufid'] = 488;
						break;
					case "BA":
						$endereco['ufid'] = 489;
						break;
					case "CE":
						$endereco['ufid'] = 490;
						break;
					case "DF":
						$endereco['ufid'] = 511;
						break;
					case "ES":
						$endereco['ufid'] = 491;
						break;
					case "GO":
						$endereco['ufid'] = 492;
						break;
					case "MA":
						$endereco['ufid'] = 493;
						break;
					case "MT":
						$endereco['ufid'] = 494;
						break;
					case "MS":
						$endereco['ufid'] = 495;
						break;
					case "MG":
						$endereco['ufid'] = 496;
						break;
					case "PA":
						$endereco['ufid'] = 497;
						break;
					case "PB":
						$endereco['ufid'] = 498;
						break;
					case "PR":
						$endereco['ufid'] = 499;
						break;
					case "PE":
						$endereco['ufid'] = 500;
						break;
					case "PI":
						$endereco['ufid'] = 501;
						break;
					case "RJ":
						$endereco['ufid'] = 502;
						break;
					case "RN":
						$endereco['ufid'] = 503;
						break;
					case "RS":
						$endereco['ufid'] = 504;
						break;
					case "RO":
						$endereco['ufid'] = 505;
						break;
					case "RR":
						$endereco['ufid'] = 506;
						break;
					case "SC":
						$endereco['ufid'] = 507;
						break;
					case "SP":
						$endereco['ufid'] = 508;
						break;
					case "SE":
						$endereco['ufid'] = 509;
						break;
					case "TO":
						$endereco['ufid'] = 510;
						break;
				}*/
			
				switch ($endereco['uf']) {
					case "AC":
						$endereco['ufid'] = 485;
						break;
					case "AL":
						$endereco['ufid'] = 486;
						break;
					case "AP":
						$endereco['ufid'] = 487;
						break;
					case "AM":
						$endereco['ufid'] = 488;
						break;
					case "BA":
						$endereco['ufid'] = 489;
						break;
					case "CE":
						$endereco['ufid'] = 490;
						break;
					case "DF":
						$endereco['ufid'] = 491;
						break;
					case "ES":
						$endereco['ufid'] = 492;
						break;
					case "GO":
						$endereco['ufid'] = 493;
						break;
					case "MA":
						$endereco['ufid'] = 494;
						break;
					case "MT":
						$endereco['ufid'] = 495;
						break;
					case "MS":
						$endereco['ufid'] = 496;
						break;
					case "MG":
						$endereco['ufid'] = 497;
						break;
					case "PA":
						$endereco['ufid'] = 498;
						break;
					case "PB":
						$endereco['ufid'] = 499;
						break;
					case "PR":
						$endereco['ufid'] = 500;
						break;
					case "PE":
						$endereco['ufid'] = 501;
						break;
					case "PI":
						$endereco['ufid'] = 502;
						break;
					case "RJ":
						$endereco['ufid'] = 503;
						break;
					case "RN":
						$endereco['ufid'] = 504;
						break;
					case "RS":
						$endereco['ufid'] = 505;
						break;
					case "RO":
						$endereco['ufid'] = 506;
						break;
					case "RR":
						$endereco['ufid'] = 507;
						break;
					case "SC":
						$endereco['ufid'] = 508;
						break;
					case "SP":
						$endereco['ufid'] = 509;
						break;
					case "SE":
						$endereco['ufid'] = 510;
						break;
					case "TO":
						$endereco['ufid'] = 511;
						break;
				}
				if($endereco['ufid'])
					$this->getResponse()->setBody(Mage::helper('core')->jsonEncode((object)$endereco));
				else 
					$this->getResponse()->setBody('Correios indisponível');
			}
		}
	}

	public function EnableWebhooksAction(){

		$validacao = $this->getRequest()->getParams();
		
		if($validacao['validacao']== Mage::getSingleton('transparente/standard')->getConfigData('validador_retorno')){

			$model = new Mage_Core_Model_Config();
			$validacao = Mage::getSingleton('transparente/standard')->getConfigData('validador_retorno');
			$status_controller = array("PAYMENT.AUTHORIZED","PAYMENT.CANCELLED","REFUND.REQUESTED");
			$webhooks = array(
				"events" => $status_controller,
				"target" =>  Mage::getUrl('Transparente/standard/success/'.'validacao/'.$validacao.'/'),
				"media" => "WEBHOOK"
			);

			if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
	          	$url = "https://sandbox.moip.com.br/v2/preferences/notifications/";
	        	$oauth = trim(Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev'));
                $header = "Authorization: OAuth {$oauth}";
                $documento = "Content-Type: application/json";
		    } else {
	        	$url = "https://api.moip.com.br/v2/preferences/notifications/";
				$oauth = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod') ;
            	 $header = "Authorization: OAuth {$oauth}";
                $documento = "Content-Type: application/json";
		    }

		    $json = json_encode($webhooks);
		    
			$result = array();
	    	$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,$url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,0); 
			curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array($header, $documento));
			curl_setopt($ch, CURLOPT_USERAGENT,'MoipMagento/2.0.0');
			curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$res = curl_exec($ch);
			$info = curl_getinfo($ch);
		 	curl_close($ch);
		 	$responseBody = json_decode($res, true);

		 	$result = array('header' => array($header, $documento),
		 					'url' => $url,
		 					"json_send" => $webhooks,
		 					"responseBody" => $responseBody,
		 					"responseCode" => $info

		 					);
		 	$json_debug = json_encode($result);
		   	
			if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
				$model->saveConfig('payment/moip_transparente_standard/webhook_key_dev', $responseBody['token'], 'default', 0);
			  } else {
			  	$model->saveConfig('payment/moip_transparente_standard/webhook_key_prod', $responseBody['token'], 'default', 0);
			}

			if($responseBody['token']){
				echo "WebHooks configurado com sucesso.";
			}
		}
		

		
	}
	
	public function ClearMoipAction(){
		$model = new Mage_Core_Model_Config();
		$validacao = $this->getRequest()->getParams();
		if($validacao['validacao']== Mage::getSingleton('transparente/standard')->getConfigData('validador_retorno')){
			if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
				$model->deleteConfig('payment/moip_transparente_standard/webhook_key_dev');
				$model->deleteConfig('payment/moip_transparente_standard/oauth_dev');

			} else {
				$model->deleteConfig('payment/moip_transparente_standard/webhook_key_prod');
				$model->deleteConfig('payment/moip_transparente_standard/oauth_prod');
				
			}
			echo "Configurações atuais foram apagadas. Por favor, repita o processo de instalação.";
		}
	}
	public function DebugKeyPublicAction() {

		$data = $this->getRequest()->getParams();
			if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {


				if($data['oauth']){
					$oauth = $data['oauth'];
				} else {
					$oauth = Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev') ;
				}
				$url = "https://sandbox.moip.com.br/v2/keys/";
				$header = "Authorization: OAuth " . $oauth;
				echo "oauth ".$oauth;

			} else {
				echo "in produtçao: ";
				$url = "https://api.moip.com.br/v2/keys/";
				if($data['oauth']){
					$oauth = $data['oauth'];
					echo "oauth ".$oauth;
				} else {
					$oauth = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod') ;
				}

				$header = "Authorization: OAuth " . $oauth;
			}

		$result = array();
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));
		curl_setopt($ch,CURLOPT_USERAGENT,'MoipMagento/2.0.0');
		$responseBody = curl_exec($ch);
		curl_close($ch);
		$responseBody = json_decode($responseBody, true);
		$public_key = $responseBody['keys']['encryption'];

	  return $public_key;
	}

}

