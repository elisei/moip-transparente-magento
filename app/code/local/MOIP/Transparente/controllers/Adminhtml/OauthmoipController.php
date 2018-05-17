<?php
class MOIP_Transparente_Adminhtml_OauthmoipController extends  Mage_Adminhtml_Controller_Action {

   
    protected function _isAllowed()
    {
        
        $aclResource = 'oauthmoip';
        return Mage::getSingleton('admin/session')->isAllowed($aclResource);
    }
    public function testAction(){

    	Mage::getSingleton('core/session')->addSuccess("Configurações atuais foram apagadas. Por favor, repita o processo de instalação.");

    	$redirect_url = (Mage::helper('core/http')->getHttpReferer() ? Mage::helper('core/http')->getHttpReferer() : Mage::helper("adminhtml")->getUrl("*/system_config/edit/section/payment/"));
			Mage::app()->getFrontController()->getResponse()->setRedirect($redirect_url);
			return $this;
    }
    public function KillWebhooksAction(){
    	$data = $this->getRequest()->getParams();
    	$documento = 'Content-Type: application/json; charset=utf-8';
        
        if ($data['environment'] == "teste") {
            $url = MOIP_Transparente_Model_Api::ENDPOINT_TEST."preferences/notifications/".$data['id'];
            $header = "Authorization: OAuth " . Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev');
            
        } else {
            $url = MOIP_Transparente_Model_Api::ENDPOINT_PROD."preferences/notifications/".$data['id'];
            $header = "Authorization: OAuth " . Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod');
        }



            $res = array();
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_NOBODY, true); 
            curl_setopt($ch, CURLOPT_HTTPHEADER, array($header, $documento));
            curl_setopt($ch,CURLOPT_USERAGENT,'MoipMagento/2.0.0');
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $res = curl_exec($ch);
            curl_close($ch);
         

			Mage::app()->cleanCache();
			Mage::getSingleton('core/session')->addSuccess('Preferência apagada.');
			$redirect_url = (Mage::helper('core/http')->getHttpReferer() ? Mage::helper('core/http')->getHttpReferer() : Mage::helper("adminhtml")->getUrl("*/system_config/edit/section/moipall/"));
			
			Mage::app()->getFrontController()->getResponse()->setRedirect($redirect_url);
			


        return $this;

    }
    public function EnableWebhooksAction(){

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
			curl_setopt($ch, CURLOPT_HTTPHEADER, array($header, $documento));
			curl_setopt($ch, CURLOPT_USERAGENT,'MoipMagento/2.0.0');
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
				Mage::app()->cleanCache();
				Mage::getSingleton('core/session')->addSuccess('Configuração de Retorno de Status Concluídas.');
				$redirect_url = (Mage::helper('core/http')->getHttpReferer() ? Mage::helper('core/http')->getHttpReferer() : Mage::helper("adminhtml")->getUrl("*/system_config/edit/section/payment/"));
				Mage::app()->getFrontController()->getResponse()->setRedirect($redirect_url);
			}
			
		
		

		
	}
	
	public function ClearMoipAction(){
			
		return Mage::helper('transparente')->ClearMoip();
		
	}

	public function OauthAction() {
		$api = $this->getApi();
		$standard = $this->getStandard();
		$data = $this->getRequest()->getParams();
		$model = new Mage_Core_Model_Config();
		
		
		$json_log = json_encode($data);
		$api->generateLog($json_log, 'MOIP_Oauth.log');
		
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
				$model->saveConfig('payment/moip_transparente_standard/oauth_dev', $oauth_decode['access_token'], 'default', 0);
				$model->saveConfig('payment/moip_transparente_standard/mpa_dev', $oauth_decode['moipAccount']['id'], 'default', 0);
				$public_key = $this->getKeyPublic($oauth_decode['access_token']);
				$model->saveConfig('payment/moip_transparente_standard/publickey_dev', $public_key, 'default', 0);
			} else {
				$oauth =  $this->getOauthAcess($data['code']);

				$oauth_decode = json_decode($oauth, true);
				$api->generateLog($oauth_decode['accessToken'], 'MOIP_Oauth.log');
				$model->saveConfig('payment/moip_transparente_standard/oauth_prod', $oauth_decode['access_token'], 'default', 0);
				$model->saveConfig('payment/moip_transparente_standard/mpa_prod', $oauth_decode['moipAccount']['id'], 'default', 0);
				$public_key = $this->getKeyPublic($oauth_decode['access_token']);
				
				$model->saveConfig('payment/moip_transparente_standard/publickey_prod', $public_key, 'default', 0);
			}
			Mage::app()->cleanCache();
			Mage::getSingleton('core/session')->addSuccess('Configuração Concluída, por favor realize o seu teste.');
			
			$redirect_url = (Mage::helper('core/http')->getHttpReferer() ? Mage::helper('core/http')->getHttpReferer() : Mage::helper("adminhtml")->getUrl("*/system_config/edit/section/payment/"));
			Mage::app()->getFrontController()->getResponse()->setRedirect($redirect_url);
		
		
	}


	public function getOauthAcess($code) {
		$api = $this->getApi();
		$documento = 'Content-Type: application/x-www-form-urlencoded; charset=utf-8';
		$api->generateLog($code, 'MOIP_Oauth.log');
		 if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
	          	$url = "https://connect-sandbox.moip.com.br/oauth/token";
	        	$header = "Authorization: Basic " . base64_encode(MOIP_Transparente_Model_Api::TOKEN_TEST . ":" . MOIP_Transparente_Model_Api::KEY_TEST);
	        	$array_json = array(
		        	'client_id' => $this->getApi()->getAppId("teste"),
		        	'client_secret' => $this->getApi()->getClienteSecret("teste"),
					'redirect_uri' => 'http://moip.o2ti.com/magento/redirect/',
					'grant_type' => 'authorization_code',
					'code' => $code
	        	);
	        	$json = http_build_query($array_json);
	      }
	      else {
              	$url = "https://connect.moip.com.br/oauth/token";
		        $header = "Authorization: Basic " . base64_encode(MOIP_Transparente_Model_Api::TOKEN_PROD . ":" . MOIP_Transparente_Model_Api::KEY_PROD);
		        $array_json = array(
			        	'client_id' =>  $this->getApi()->getAppId("prod"),
			        	'client_secret' => $this->getApi()->getClienteSecret("prod"),
						'redirect_uri' => 'http://moip.o2ti.com/magento/redirect/',
						'grant_type' => 'authorization_code',
						'code' => $code
		        	);
		       $json = http_build_query($array_json);
	      }
	      $result = array();
	      $ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,$url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 6000);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
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


	public function getApi()
    {
        $api = Mage::getModel('transparente/api');
        return $api;
    }
    public function getStandard() {
		return Mage::getSingleton('transparente/standard');
	}

    
}