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
class MOIP_Transparente_LoginController extends Mage_Core_Controller_Front_Action {

	const URL_DEV 				= "https://sandbox.moip.com.br/";
	const URL_CONNECT_DEV 		= "https://connect-sandbox.moip.com.br/";
	const SCOPE     			= 'MANAGE_ACCOUNT_INFO';

	const TOKEN_DEV 			= "VPL0MCCFINDWOMA85B8UMJHW2VOPYTLL";
	const KEY_TEST 				= "Y6LTLBDNRKST1VEXNXMHI07MXLY3VEDW3LWNQHM3";
	const APP_ID_SAND  			= 'APP-LPEXI3SLWA8R';
	const APP_SECRET_DEV 		= '08b6eb68f09d418ba20c011d8059e80c';

	const URL_PROD 				= "https://api.moip.com.br/";
	const URL_CONNECT_PROD 		= "https://connect.moip.com.br/";

	const TOKEN_PROD 			= "FEE5P78NA6RZAHBNH3GLMWZFWRE7IU3D";
	const KEY_PROD 				= "Y8DIATTADUNVOSXKDN0JVDAQ1KU7UPJHEGPM7SBA";
	const APP_ID_PROD  			= 'APP-FSY889YYQWLZ';
	const APP_SECRET_PROD 		= '00b54376ab174246ad4ad767a43bd99f';


	public function _prepareLayout()
	{
		parent::_prepareLayout();
	}

	public function getApi()
    {
        $api = Mage::getModel('transparente/api');
        return $api;
    }

    public function getUriConnect(){
        $uri = Mage::getUrl('Transparente/login/oauth/');
        return $uri;
    }

    public function getConnectMoip(){
    	if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
        	$url = SELF::URL_CONNECT_DEV.'/oauth/authorize/?response_type=AUTHORIZATION_CODE&client_id='.SELF::APP_ID_SAND.'&redirectUri='.$this->getUriConnect().'&scope='.SELF::SCOPE;
        } else {
        	$url = SELF::URL_CONNECT_PROD.'/oauth/authorize/?response_type=AUTHORIZATION_CODE&client_id='.SELF::APP_ID_PROD.'&redirectUri='.$this->getUriConnect().'&scope='.SELF::SCOPE;
        }
        return $url; 
    }
    public function getAllValuesAction()
    {
    	$this->loadLayout();
    	$params = $this->getRequest()->getParams();
    	$json_moip 	= $this->getRequest()->getRawBody();
    	$this->getResponse()->setBody(Mage::helper('core')->jsonEncode((object)$json_moip));
    }


	public function OauthAction(){
		$this->loadLayout();
		$params = $this->getRequest()->getParams();

		if($params){
			$getRequestCode 	= $this->getAcesstoken($params['code']); 
			$result 			= $getRequestCode['result'];
			$result_decode 		= json_decode($result, 'true');
			$MPA 				= $result_decode['moipAccount']['id'];
			$accountInfo 		= $this->getAccountInfo($MPA);
			$statusCustomer 	= $this->getClientMage($accountInfo);

			$this->ClearAndAddProductCart();

			$checkout			= Mage::getUrl('checkout/onepage/');
			Mage::app()->getResponse()->setRedirect($checkout, 301)->sendHeaders();
			
		}	 	
		
	}


	
	protected function _getSession()
	    {
	        $session = Mage::getSingleton('adminhtml/session_quote');

	        return $session;
	    }


	public function ClearAndAddProductCart(){
		$product_id = 2;
		$qty 		= 1;
		$cart = Mage::helper('checkout/cart')->getCart();
	    $items = $cart->getItems();
	    $itemCount = count($items);
	    if($itemCount > 1)
	    {
	        $i = 0;
	        foreach($items as $item)
	        {
	            $i++;
	            if($i == $itemCount)
	            {
	                if($item->getProduct()->getId() == $product_id)
	                {
	                    $itemId = $item->getItemId();
	                    $cart->removeItem($itemId)->save();
	                }
	            }
	        }
	    }
	    else
	    {
	        foreach($items as $item)
	        {
	            if($item->getProduct()->getId() == $product_id)
	            {
	                try{
	                    $quote = Mage::getSingleton('checkout/session')->getQuote();
	                    $quote->removeAllItems();
	                    $quote->save();
	                }catch (Exception $e)
	                {
	                    Mage::log('Failed to remove item from cart'.$e.'.');
	                }
	            }
	        }
	    }
	   
		
		
		$product = Mage::getModel('catalog/product')->load($product_id);
		$quote = Mage::getSingleton('checkout/session')->getQuote();
		$quote->addProduct($product, $qty);
		$quote->collectTotals()->save();
	}
	
    public function getAcesstoken($code){
		
		if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
			$url 		= SELF::URL_CONNECT_DEV."oauth/token";
			$header 	= "Authorization: Basic " . base64_encode(SELF::TOKEN_DEV.":".SELF::KEY_TEST);
			$appID 		= SELF::APP_ID_SAND;
			$appSecret	= SELF::APP_SECRET_DEV;
		} else {
			$url 		= SELF::URL_CONNECT_PROD."oauth/token";
			$header 	= "Authorization: Basic " . base64_encode(SELF::TOKEN_PROD.":".SELF::KEY_PROD);
			$appID 		= SELF::APP_ID_PROD;
			$appSecret	= SELF::APP_SECRET_PROD;
		}

		$documento = "Content-Type: application/x-www-form-urlencoded";
		$data = array(
				"client_id" => $appID,
				"code" => $code,
				"client_secret" => $appSecret,
				"grant_type" => "authorization_code",
				"redirect_uri" => $this->getUriConnect()
			);

		$enconde_data =  http_build_query($data);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 500);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $enconde_data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array($header, $documento));
		curl_setopt($ch,CURLOPT_USERAGENT,'MoipMagento/2.0.0');
		$info_curl = curl_getinfo($ch);
		$res = curl_exec($ch);
	 	curl_close($ch);
	 	$debug['url'] = $url;
	 	$debug['header'] = $header;
	 	$debug['documento'] = $documento;
	 	$debug['enconde_data'] = $enconde_data;
	 	$debug['result'] = $res;
	 	$this->getApi()->generateLog("----------- getAcesstoken - Inicio - url e header --------------", 'Moip_RegistreCustomer.log');
		$this->getApi()->generateLog("Url ".$url." header" .$header." ".$documento, 'Moip_RegistreCustomer.log');
		$this->getApi()->generateLog("----------- getAcesstoken - Dados --------------", 'Moip_RegistreCustomer.log');
		$this->getApi()->generateLog($enconde_data, 'Moip_RegistreCustomer.log'); 
		$this->getApi()->generateLog("----------- getAcesstoken - Resposta  --------------", 'Moip_RegistreCustomer.log');
		$this->getApi()->generateLog($res, 'Moip_RegistreCustomer.log');
		$this->getApi()->generateLog("----------- getAccountInfo - CurlInfo  --------------", 'Moip_RegistreCustomer.log');
		$this->getApi()->generateLog($info_curl, 'Moip_RegistreCustomer.log');
		$this->getApi()->generateLog("----------- getAcesstoken - Fim  --------------", 'Moip_RegistreCustomer.log');
	 	return $debug;

	}


    public function getAccountInfo($MPA){
    	
    	if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
			$url 		= SELF::URL_DEV."v2/accounts/".$MPA;
			$header 	= "Authorization: Basic " . base64_encode(SELF::TOKEN_DEV.":".SELF::KEY_TEST);
		} else {
			$url 		= SELF::URL_PROD."v2/accounts/".$MPA;
			$header 	= "Authorization: Basic " . base64_encode(SELF::TOKEN_PROD.":".SELF::KEY_PROD);
		}
		$documento = "Content-Type: application/json; charset=utf-8";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 500);
		curl_setopt($ch, CURLOPT_POST, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array($header, $documento));
		curl_setopt($ch,CURLOPT_USERAGENT,'MoipMagento/2.0.0');
		$res = curl_exec($ch);
		$info_curl = curl_getinfo($ch);
	 	curl_close($ch);
		$result = json_decode($res);
		$this->getApi()->generateLog("----------- getAccountInfo - Inicio - url e header --------------", 'Moip_RegistreCustomer.log');
		$this->getApi()->generateLog("Url ".$url." header" .$header." ".$documento, 'Moip_RegistreCustomer.log');
		$this->getApi()->generateLog("----------- getAccountInfo - Resposta  --------------", 'Moip_RegistreCustomer.log');
		$this->getApi()->generateLog($res, 'Moip_RegistreCustomer.log');
		$this->getApi()->generateLog("----------- getAccountInfo - CurlInfo  --------------", 'Moip_RegistreCustomer.log');
		$this->getApi()->generateLog($info_curl, 'Moip_RegistreCustomer.log');
		$this->getApi()->generateLog("----------- getAccountInfo - Fim  --------------", 'Moip_RegistreCustomer.log');
	 	return $result; 

    }

    public function getClientMage($DataMoip){
    		$email 		= $DataMoip->email->address;
    		$mpa 		= $DataMoip->id;
			$websiteId 	= Mage::app()->getWebsite()->getId();
			$customer 	= Mage::getModel('customer/customer');
			
			$customer->setWebsiteId($websiteId);
		 	$customer->loadByEmail($email);

			if ($customer->getId()) {
				$this->loginUser($email,$mpa);
				return;
			} else {
				$this->setRegistreCustomer($DataMoip);
				$this->loginUser($email,$mpa);
				return;
			}
    }

    public function setRegistreCustomer($DataMoip){
    	$this->getApi()->generateLog($DataMoip, 'Moip_RegistreCustomer.log');
    	$customer 		= Mage::getModel("customer/customer");
		$customAddress 	= Mage::getModel('customer/address');
		$websiteId 		= Mage::app()->getWebsite()->getId();
		$store 			= Mage::app()->getStore();

		$customer->setWebsiteId($websiteId);
		$customer->setStoreId($storeId);

		$person			= $DataMoip->person; 
		$firstname		= $DataMoip->person->name;
		$lastname 		= $DataMoip->person->lastName;
		$email 			= $DataMoip->email->address;
		$pass			= $DataMoip->id;
		$password_hash 	= md5($pass);
		$taxvat 		= $DataMoip->person->taxDocument->number;
		if(isset($DataMoip->company)){
			$address_user	= $DataMoip->company->address;
			$telCode 		= $DataMoip->company->phone->areaCode;
			$telNumber		= $DataMoip->company->phone->number;
			$state 			= $this->getEstadoSigla($address_user->state);
		} else {
			$address_user	= $DataMoip->person->address;
			$telCode 		= $DataMoip->person->phone->areaCode;
			$telNumber		= $DataMoip->person->phone->number;
			$state 			= $this->getEstadoSigla($address_user->state);
		}
		
		$dob			= $DataMoip->person->birthDate;
		
		$customer->setGroupId(1)
					->setFirstname($firstname)
					->setLastname($lastname)
					->setEmail($email)
					->setTaxvat($taxvat)
					->setDob($dob)
					->setPassword($password_hash);
		if(isset($DataMoip->company)){
			$company 		= $DataMoip->company;
			$tipopessoa 	= 0;
			$razaosocial 	= $company->businessName;
			$nomefantasia	= $company->name;
			$cnpj			= $company->taxDocument->number;
			$insestadual	= "Isento";
			$customer->setTipopessoa(0)->setRazaosocial($razaosocial)->setNomefantasia($nomefantasia)->setInsestadual($insestadual)->setCnpj($cnpj);
		} else {
			$customer->setTipopessoa(1);
		}

				
		try {
				$customer->save();
				
					$_custom_address = array (
													'firstname' =>$firstname,
													'lastname' => $lastname,
													'street' => array (
														'0' => $address_user->street,
														'1' => $address_user->streetNumber,
														'2' => $address_user->complement,
														'3' => $address_user->district,
													),
													'city' => $address_user->city,
													'region_id' => $state,
													'region' => $address_user->state,
													'postcode' => $address_user->zipCode,
													'country_id' => 'BR',
													'telephone' => $telCode.$telNumber,
													'fax' => $telCode.$telNumber
												);
				
				$customAddress->setData($_custom_address)
							->setCustomerId($customer->getId())
							->setIsDefaultBilling('1')
							->setIsDefaultShipping('1')
							->setSaveInAddressBook('1');

				try {
					$customAddress->save();
				} catch (Exception $e) {
				    Zend_Debug::dump($e->getMessage());
				}

		} catch (Exception $e) {
		    Zend_Debug::dump($e->getMessage());
		}

    }
    public function loginUser($email,$password){
		    Mage::getSingleton('core/session', array('name' => 'frontend'));
			$session = Mage::getSingleton('customer/session');
			$session->start();

		    $websiteId = Mage::app()->getWebsite()->getId();
		    $store = Mage::app()->getStore();
		    $customer = Mage::getModel("customer/customer");
		    $customer->website_id = $websiteId;
		    $customer->setStore($store);
		    try {
		        $customer->loadByEmail($email);
		        $session = Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
		        $session->login($email, $password);
		    }catch(Exception $e){

		    }

	}
	private function getEstadoSigla($sigla){ 
		$region = Mage::getModel('directory/region')->loadByCode($sigla, 'BR');
		if($region){
			return $region->getId(); 	
		} else {
			return !1;
		}
		
	}
    public function getEstadoExtenso($UF)
	{
		$UF = strtolower($UF);
		switch ($UF) {
				case "acre":
					$endereco['ufid'] = 485;
					break;
				case "alagoas":
					$endereco['ufid'] = 486;
					break;
				case "amapá":
					$endereco['ufid'] = 487;
					break;
				case "amazonas":
					$endereco['ufid'] = 488;
					break;
				case "bahia":
					$endereco['ufid'] = 489;
					break;
				case "ceará":
					$endereco['ufid'] = 490;
					break;
				case "distrito federal":
					$endereco['ufid'] = 491;
					break;
				case "espirito santo":
					$endereco['ufid'] = 492;
					break;
				case "goias":
					$endereco['ufid'] = 493;
					break;
				case "maranhão":
					$endereco['ufid'] = 494;
					break;
				case "mato grosso":
					$endereco['ufid'] = 495;
					break;
				case "mato grosso do sul":
					$endereco['ufid'] = 496;
					break;
				case "minas gerais":
					$endereco['ufid'] = 497;
					break;
				case "pará":
					$endereco['ufid'] = 498;
					break;
				case "paraíba":
					$endereco['ufid'] = 499;
					break;
				case "paraná":
					$endereco['ufid'] = 500;
					break;
				case "pernambuco":
					$endereco['ufid'] = 501;
					break;
				case "piauí":
					$endereco['ufid'] = 502;
					break;
				case "rio de janeiro":
					$endereco['ufid'] = 503;
					break;
				case "rio grande do norte":
					$endereco['ufid'] = 504;
					break;
				case "rio grande do sul":
					$endereco['ufid'] = 505;
					break;
				case "rondônia":
					$endereco['ufid'] = 506;
					break;
				case "roraima":
					$endereco['ufid'] = 507;
					break;
				case "santa catarina":
					$endereco['ufid'] = 508;
					break;
				case "são paulo":
					$endereco['ufid'] = 509;
					break;
				case "sergipe":
					$endereco['ufid'] = 510;
					break;
				case "tocantins":
					$endereco['ufid'] = 511;
					break;
			}
			return $endereco['ufid'];
	}

}	
