<?php
require_once 'Mage/Checkout/controllers/OnepageController.php';
class MOIP_Onestepcheckout_IndexController extends Mage_Checkout_OnepageController
{ 
	protected $notshiptype=0;
	
	public function getCheckout() {
		return Mage::getSingleton('checkout/session');
	}
	public function getQuote() {
		return Mage::getSingleton('checkout/session')->getQuote();
	}
	public function getOnepage() {
		return Mage::getSingleton('checkout/type_onepage');
	}

	

	protected function _getQuote() {
		return Mage::getSingleton('checkout/cart')->getQuote();
	}
	public function cadastroAction() {

		
		if ($this->_initAction()) {
			if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
				if ($blocks=$this->getLayout()->getBlock('checkout.onepage')) {
					$blocks=$this->getLayout()->getBlock('checkout.onepage')->unsetChildren();
				}

			$this->renderLayout();
			} else {
				$this->_redirect('checkout/onepage');	
			}
		}
		else{
			$this->_redirect('checkout/cart');
		}

	}
	public function EditAddressAction() {
		$this->loadLayout();
		$this->renderLayout();
		return $this;
	}

	public function getValidateAddress(){
		$customer 		= Mage::getSingleton('customer/session')->getCustomer();
		$billing_id 	= $customer->getDefaultBilling();
		$shipping_id 	= $customer->getDefaultShipping();
		$validade = Mage::helper('onestepcheckout')->validate($billing_id, $shipping_id, $customer);

		if(!$validade){
			//'id' => 1,
			$url_edit_address = Mage::getUrl('checkout/onepage/EditAddress', array('_secure'=>true,'id' =>$billing_id));
			Mage::app()->getFrontController()->getResponse()->setRedirect($url_edit_address)->sendResponse();
		} else {
			return $this;
		}
	}

	private function _getRegionId($sigla){ 
		$region = Mage::getModel('directory/region')->loadByCode($sigla, 'BR');
		if($region){
			return $region->getId(); 	
		} else {
			return !1;
		}
		
	}
	public function buscaCepAction() {
		$data = $this->getRequest()->getParams();
		if($data){
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
				$endereco['ufid'] = $this->_getRegionId($endereco['uf']);
				$this->getResponse()->setBody(Mage::helper('core')->jsonEncode((object)$endereco));
			} else {
				$this->getResponse()->setBody('Correios indisponível');
			}
		}
	}
	
	public function indexAction() {
		if (!Mage::getStoreConfig('onestepcheckout/config/allowguestcheckout')) {
				if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
					Mage::getSingleton('checkout/session')->setCartWasUpdated(false);
					Mage::getSingleton('customer/session')->setBeforeAuthUrl(Mage::getUrl('checkout/onepage/', array('_secure'=>true)));


					$this->_redirect('customer/account/login', array(
									'referer' => Mage::helper('core')->urlEncode(Mage::getUrl("checkout/onepage/")),
									'context' => 'checkout'
								));
					return;
				}	
		}

		
		if(Mage::getStoreConfig('onestepcheckout/layout/use_pre_cadastro')==1){
			if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
				Mage::getSingleton('checkout/session')->setCartWasUpdated(false);
				Mage::getSingleton('customer/session')->setBeforeAuthUrl(Mage::getUrl('checkout/onepage/', array('_secure'=>true)));
				$this->_redirect('checkout/onepage/cadastro');
				return;
			}	else {
				$this->renderLayout();
			}
		}
			
		
		if ($this->_initAction()) {
				if (Mage::getSingleton('customer/session')->isLoggedIn()) {
					$this->getValidateAddress();
				}
				if ($blocks=$this->getLayout()->getBlock('checkout.onepage')) {
					$blocks=$this->getLayout()->getBlock('checkout.onepage')->unsetChildren();
				}
			
			$this->renderLayout();
		}
		else
			$this->_redirect('checkout/cart');

	}

	public function _initAction() {
		
		
		if (!Mage::getStoreConfig('checkout/options/guest_checkout')) {
			Mage::getSingleton('checkout/session')->addError($this->__('O checkout não está disponível para perfils anônimo. Altere a configuração sua loja em > Sistema > Configurações >  Fechar Pedido.'));
			return false;
		}

		
		/*if (Mage::getStoreConfig('checkout/options/customer_must_be_logged') == 0) {
			Mage::getSingleton('checkout/session')->addError($this->__('O checkout não está disponível para perfils anônimo. Altere a configuração sua loja em > Sistema > Configurações >  Fechar Pedido.'));
			return false;
		}*/

		if (!Mage::helper('checkout')->canOnepageCheckout()) {
			Mage::getSingleton('checkout/session')->addError($this->__('The onepage checkout is disabled.'));
			return false;
		}
		$quote = $this->getOnepage()->getQuote();
		if (!$quote->hasItems() || $quote->getHasError()) {
			return false;
		}
		if (!$quote->validateMinimumAmount()) {
			$error = Mage::getStoreConfig('sales/minimum_order/error_message');
			Mage::getSingleton('checkout/session')->addError($error);
			return false;
		}
		Mage::getSingleton('checkout/session')->setCartWasUpdated(false);
		Mage::getSingleton('customer/session')->setBeforeAuthUrl(Mage::getUrl('checkout/onepage/', array('_secure'=>true)));
		$this->initInfoaddress();
		$this->getOnepage()->initCheckout();

		
		$this->initshippingmethod();
		$this->initpaymentmethod();
		
		$this->loadLayout();
		$this->_initLayoutMessages('customer/session');
		$this->_initLayoutMessages('checkout/session');
		$this->_initLayoutMessages('catalog/session');
		Mage::getSingleton('catalog/session')->getData('messages')->clear();
		Mage::getSingleton('checkout/session')->getData('messages')->clear();
		$this->getLayout()->getBlock('head')->setTitle($this->__('Checkout'));

		return true;
	}
	public function initshippingmethod() {

		
		$guessCustomer = Mage::getSingleton('checkout/session')->getQuote();
		$addresses=$guessCustomer->getShippingAddress();
		$applyrule=$this->getQuote()->getAppliedRuleIds();
		$applyaction=Mage::getModel('salesrule/rule')->load($applyrule)->getSimpleAction();

		if ($applyaction!='cart_fixed') {
			Mage::getSingleton('checkout/session')->getQuote()->setTotalsCollectedFlag(false);
		}
		$list_shipmethod=$addresses->getGroupedAllShippingRates();
		
		
		return;
	}
	public function _canUseMethod($method) {
		if (!$method->canUseForCountry($this->getQuote()->getBillingAddress()->getCountry())) {
			return false;
		}

		if (!$method->canUseForCurrency(Mage::app()->getStore()->getBaseCurrencyCode())) {
			return false;
		}
		$total = Mage::getSingleton('checkout/session')->getQuote()->getBaseGrandTotal();
		$minTotal = $method->getConfigData('min_order_total');
		$maxTotal = $method->getConfigData('max_order_total');
		if ((!empty($minTotal) && ($total < $minTotal)) || (!empty($maxTotal) && ($total > $maxTotal))) {
			return false;
		}
		return true;
	}
	public function initpaymentmethod() {
		$quote = Mage::getSingleton('checkout/session')->getQuote();
		$methodCode = '';
		if ($quote->isVirtual()) {
		    $quote->getBillingAddress()->setPaymentMethod($methodCode);
		} else {
		    $quote->getShippingAddress()->setPaymentMethod($methodCode);
		}
		$quote->save();
		
		
		


		
		
	}
	public function initInfoaddress() {
		$coutryid='';$postcode='';$region='';$regionid='';$city='';$customerAddressId='';
		$coutryid = 'BR';

		$postData=array(
			'address_id'=>'',
			'firstname'=>'',
			'lastname'=>'',
			'company'=>'',
			'email'=>'',
			'street'=>array('', '', '', ''),
			'city'=>'',
			'region_id'=>'',
			'region'=>'',
			'postcode'=>'',
			'country_id'=>'BR',
			'telephone'=>'',
			'fax'=>'',
			'save_in_address_book'=>'0'
		);
		if (Mage::getSingleton('customer/session')->isLoggedIn()) {
			$customerAddressId =Mage::getSingleton('customer/session')->getCustomer()->getDefaultBilling();
		}

			$postData = $this->filterdata($postData);
			if (version_compare(Mage::getVersion(), '1.4.0.1', '>=')){
					if (isset($postData['email']))
						$postData['email'] = trim($postData['email']);
					$data = $this->_filterPostData($postData);
			}

			else{
					if (isset($postData['email']))
						$postData['email'] = trim($postData['email']);
					$data=$postData;
			}

		if (($postData['country_id']!='')  || $customerAddressId) {
			$this->saveBilling($data, $customerAddressId);
			$this->saveShipping($data, $customerAddressId);
		}
		else {

			$this->_getQuote()->getShippingAddress()
			->setCountryId('')
			->setPostcode('')
			->setCollectShippingRates(true);
			$this->_getQuote()->save();
			$this->loadLayout()->renderLayout();
			return;
		}

	}
	public function saveShipping($data, $customerAddressId) {
		if (empty($data)) {
			return array('error' => -1, 'message' => $this->_helper->__('Invalid data.'));
		}
		$address = $this->getQuote()->getShippingAddress();
		if (!empty($customerAddressId)) {
			$customerAddress = Mage::getModel('customer/address')->load($customerAddressId);
			if ($customerAddress->getId()) {
				if ($customerAddress->getCustomerId() != $this->getQuote()->getCustomerId()) {
					return array('error' => 1,
						'message' => $this->_helper->__('Customer Address is not valid.')
					);
				}
				$address->importCustomerAddress($customerAddress);
			}
		} else {
			unset($data['address_id']);
			$address->addData($data);
		}
		$address->implodeStreetAddress();
		$address->setCollectShippingRates(true);
	}
	public function saveBilling($data, $customerAddressId) {
		if (empty($data)) {
			return array('error' => -1, 'message' => $this->_helper->__('Invalid data.'));
		}
		$address = $this->getQuote()->getBillingAddress();
		if (!empty($customerAddressId)) {
			$customerAddress = Mage::getModel('customer/address')->load($customerAddressId);
			if ($customerAddress->getId()) {
				if ($customerAddress->getCustomerId() != $this->getQuote()->getCustomerId()) {
					return array('error' => 1,
						'message' => $this->_helper->__('Customer Address is not valid.')
					);
				}
				$address->importCustomerAddress($customerAddress);
			}
		}
		else {
			unset($data['address_id']);
			$address->addData($data);
		}
		$address->implodeStreetAddress();
		if (!$this->getQuote()->isVirtual()) {
			$shipping = $this->getQuote()->getShippingAddress();
			$shipping->setSameAsBilling(0);

		}
	}
	protected function _processValidateCustomer(Mage_Sales_Model_Quote_Address $address) {
		$dob = '';
		if ($address->getDob()) {
			$dob = Mage::app()->getLocale()->date($address->getDob(), null, null, false)->toString('yyyy-MM-dd');
			$this->getQuote()->setCustomerDob($dob);
		}
		if ($address->getTaxvat()) {
			$this->getQuote()->setCustomerTaxvat($address->getTaxvat());
		}
		if ($address->getGender()) {
			$this->getQuote()->setCustomerGender($address->getGender());
		}
		if ($this->getQuote()->getCheckoutMethod()=='register') {
			$customer = Mage::getModel('customer/customer');
			$this->getQuote()->setPasswordHash($customer->encryptPassword($address->getCustomerPassword()));
			$customer_data = array(
					'firstname'    => 'firstname',
					'lastname'     => 'lastname',
					'email'        => 'email',
					'password'     => 'customer_password',
					'confirmation' => 'confirm_password',
					'taxvat'       => 'taxvat',
					'gender'       => 'gender',
				) ;
			foreach ($customer_data as $key => $dataKey) {
				$customer->setData($key, $address->getData($dataKey));
			}
			if ($dob) {
				$customer->setDob($dob);
			}
			$validationResult = $customer->validate();
			if (true !== $validationResult && is_array($validationResult)) {
				return array(
					'error'   => -1,
					'message' => implode(', ', $validationResult)
				);
			}
		} elseif (self::METHOD_GUEST == $this->getQuote()->getCheckoutMethod()) {
			$email = $address->getData('email');
			if (!Zend_Validate::is($email, 'EmailAddress')) {
				return array(
					'error'   => -1,
					'message' => $this->_helper->__('Invalid email address "%s"', $email)
				);
			}
		}
		return true;
	}
	public function updateshippingmethodAction() {
		error_reporting(E_ALL);
		ini_set("display_errors",1);
		
		
		 $data = $this->getRequest()->getPost('shipping_method', '');
         $result = $this->getOnepage()->saveShippingMethod($data);
		$this->initpaymentmethod();
            
		if (!isset($result['error'])) {
			  Mage::dispatchEvent(
                    'checkout_controller_onepage_save_shipping_method',
                     array(
                          'request' => $this->getRequest(),
                          'quote'   => $this->getOnepage()->getQuote()));
                $this->getOnepage()->getQuote()->collectTotals();
			
		}

		$this->getOnepage()->getQuote()->collectTotals()->save();
		$this->updatereviewmethodAction();

	}
	public function updatepaymentmethodAction() {
		error_reporting(E_ALL);
		ini_set("display_errors",1);
		$shipping = $this->getRequest()->getPost('shipping_method', '');
		$data=$this->getRequest()->getPost('payment','');
		try{
				$this->getOnepage()->savePayment($data);
				$result = $this->getOnepage()->saveShippingMethod($shipping);
					if (!isset($result['error'])) {
						Mage::dispatchEvent(
			                    'checkout_controller_onepage_save_shipping_method',
			                     array(
			                          'request' => $this->getRequest(),
			                          'quote'   => $this->getOnepage()->getQuote()));
								$this->getOnepage()->getQuote()->collectTotals()->save();
					}

            }
        	catch (Exception $e) {
				$this->_getQuote()->save();
			}
		$this->updatereviewmethodAction();
	}

	public function updatereviewmethodAction() {
		return $this->loadLayout()->renderLayout();
	}

	public function updateemailmsgAction() {
		$email = (string) $this->getRequest()->getParam('email');
		$websiteid=Mage::app()->getWebsite()->getId();
		$store=Mage::app()->getStore();
		$customer=Mage::getSingleton('customer/customer');
		$customer->website_id=$websiteid;
		$customer->setStore($store);
		$customer->loadByEmail($email);
		if ($customer->getId()) {
			return $this->getResponse()->setBody(0);
		}
		else {
			return $this->getResponse()->setBody(1);
		}
		return;
	}

	public function removeproductAction() {
		$id = (int) $this->getRequest()->getParam('id');
		$hasgiftbox=$this->getRequest()->getParam('hasgiftbox');
		if ($id) {
			try {
				Mage::getSingleton('checkout/cart')->removeItem($id)->save();
			} catch (Exception $e) {
				$success=0;
				
				return ;
			}
		}
	}

	public function updateqtyAction() {
		$datacart = $this->getRequest()->getParam('cart');
        $filter = new Zend_Filter_LocalizedToNormalized(
            array('locale' => Mage::app()->getLocale()->getLocaleCode())
        );
        foreach ($datacart as $index => $data) {
            if (isset($data['qty'])) {
                $datacart[$index]['qty'] = $filter->filter($data['qty']);
            }
        }
        $cart = Mage::getSingleton('checkout/cart');

        $cart->updateItems($datacart)
            ->save();
	    Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
	}

	public function updatecouponAction() {
		$this->_initLayoutMessages('checkout/session');
		$data = $this->getRequest()->getPost('shipping_method', '');
		$couponCode = (string) $this->getRequest()->getParam('coupon_code');

		Mage::getSingleton('checkout/session')
		    ->getQuote()
		    ->setCouponCode(strlen($couponCode) ? $couponCode : '')
		    ->collectTotals()
		    ->save();

		$json_array = array('status' => 'success');

		return $this->getResponse()->setBody(json_encode($json_array));

	}

	public function renderReview() {
		$layout=$this->getLayout();
		$update = $layout->getUpdate();
		$update->load('checkout_onepage_index');
		$layout->generateXml();
		$layout->generateBlocks();
		$output=$layout->getBlock('root')->toHtml();
		return $output;
	}

	public function renderCoupon() {
		$layout=$this->getLayout();
		$update = $layout->getUpdate();
		$update->load('checkout_onepage_index');
		$layout->generateXml();
		$layout->generateBlocks();
		$output = $layout->getBlock('checkout.onepage.coupon')->toHtml();
		return $output;
	}

	public function renderGiftbox() {
		$layout=$this->getLayout();
		$update = $layout->getUpdate();
		$update->load('checkout_onepage_index');
		$layout->generateXml();
		$layout->generateBlocks();
		$output = $layout->getBlock('onestepcheckout.onepage.shipping_method.additional')->toHtml();
		return $output;
	}

	public function updatepaymenttypeAction() {
			$this->initpaymentmethod();
			return $this->loadLayout()->renderLayout();
	}

	public function updateshippingtypeAction() {
		$this->notshiptype=1;
		if ($this->getRequest()->isPost()) {
			if ($this->getRequest()->getPost('ship_to_same_address')=="1") {
				$type_send="billing";
			}
			else {
				$type_send="shipping";
			}
			$post_data=$this->getRequest()->getPost($type_send, array());
			$customerAddressId = $this->getRequest()->getPost($type_send.'address_id');
		}
		if (isset($post_data['country_id'])) {
			$countryid = $post_data['country_id'];
		} else {
			$countryid = "BR";
		}
		$this->_getQuote()
				->getShippingAddress()
				->setCountryId($countryid)
				->setPostcode($post_data['postcode'])
				->collectTotals()
				->setCollectShippingRates(true)
				->collectShippingRates();
			$this->_getQuote()->save();
			$this->loadLayout()->renderLayout();
	}

	public function _getSession() {
		Mage::getSingleton('customer/session');
	}
	public function CreataAccountInitAction(){
		$this->getOnepage()->getQuote()->setCheckoutMethod('register');

		$postData = $this->getRequest()->getPost('billing', array());
		$email = trim($postData['email']);
		$websiteId = Mage::app()->getWebsite()->getId();
		$store = Mage::app()->getStore();

		$customer = Mage::getModel("customer/customer");
		$customer ->setWebsiteId($websiteId)
		         	->setStore($store)->setEmail($email)
		            ->setFirstname($postData['firstname'])
		            ->setLastname($postData['lastname']);

		if(isset($postData['gender'])){
			$gender = $postData['gender'];
			$customer->setGender($gender);
		} else {
			$gender = null;
		}
		
		if(isset($postData['dob'])){
			$dob = Mage::app()->getLocale()->date($postData['dob'], null, null, false)->toString('yyyy-MM-dd');
			$customer->setDob($dob);
		} else {
			$dob = null;
		}


		if(isset($postData['taxvat'])){
			$taxvat = $postData['taxvat'];
			$customer->setTaxvat($taxvat);
		} else {
			$taxvat = null;
		}

		if(isset($postData['tipopessoa'])){
			$tipopessoa = $postData['tipopessoa'];
		} else {
			$tipopessoa = "1";
		}

		if(isset($postData['cnpj'])){
			$cnpj = $postData['cnpj'];
		} else {
			$cnpj = "";
		}
			if(isset($postData['insestadual'])){
			$insestadual = $postData['insestadual'];
		} else {
			$insestadual = "";
		}
		if(isset($postData['nomefantasia'])){
			$nomefantasia = $postData['nomefantasia'];
		} else {
			$nomefantasia = "";
		}
		if(isset($postData['razaosocial'])){
			$razaosocial = $postData['razaosocial'];
		} else {
			$razaosocial = "";
		}
		

		
		    $customer->setTipopessoa($tipopessoa)
				->setCnpj($cnpj)
				->setinsestadual($insestadual)
				->setNomefantasia($nomefantasia)
				->setRazaosocial($razaosocial)
				->setPassword($postData['confirm_password']);
			

		try{

			$customer->save();
			Mage::getSingleton('customer/session')->loginById($customer->getId());
			$this->saveAddress('billing', $postData);
			try {
				$this->_redirect('checkout/onepage/');
				return;
			} catch (Exception $e) {
				$this->_redirect('checkout/onepage/');
				return;
			}



		}
		catch (Exception $e) {
		    	$this->_redirect('checkout/onepage/');
				return;
		}

	}
	public function updateloginAction() {
		$email=$this->getRequest()->getPost('email');
		$password=$this->getRequest()->getPost('password');
		if ($this->isCustomerLoggedIn()) {
			$this->_redirect('*/*/');
			return;
		}
		if ($this->getRequest()->isPost()) {
			if (!empty($email) && !empty($password)) {
				try{
					Mage::getSingleton('customer/session')->login($email, $password);
				}catch(Mage_Core_Exception $e) {
				}catch(Exception $e) {
				}
			}
		}
		if (Mage::getSingleton('customer/session')->isLoggedIn()) {
				return $this->getResponse()->setBody(1);
		}
		else {
			return $this->getResponse()->setBody(0);
		}
	}
	public function forgotpassAction() {
		$email=$this->getRequest()->getPost('email');
		$emailerror="0";
		if ($email) {
			if (!Zend_Validate::is($email, 'EmailAddress')) {
				$this->_getSession()->setForgottenEmail($email);
				$emailerror="0";
				return $this->getResponse()->setBody($emailerror);

			}
			$customer = Mage::getModel('customer/customer')
			->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
			->loadByEmail($email);
			if ($customer->getId()) {
				try {
					$newPassword = $customer->generatePassword();
					$customer->changePassword($newPassword, false);
					$customer->sendPasswordReminderEmail();
					$emailerror="1";
					return $this->getResponse()->setBody($emailerror);
				}
				catch (Exception $e) {
				}
			}
			else {
				$emailerror="2";
				Mage::getSingleton('customer/session')->setForgottenEmail($email);
				return $this->getResponse()->setBody($emailerror);
			}
		} else {
			$emailerror="0";
			return $this->getResponse()->setBody($emailerror);
		}
	}
	public function updateordermethodAction() {

		if (!$this->isCustomerLoggedIn()) {
			$result_save_method = $this->getOnepage()->saveCheckoutMethod('register');
		}
		$data_save_billing = $this->getRequest()->getPost('billing', array());
		$data_save_billing['email'] = trim($data_save_billing['email']);
		if ($this->isCustomerLoggedIn()) {
			$postData = $this->getRequest()->getPost('billing', array());
			$_dob = $this->getLayout()->createBlock('customer/widget_dob');
			$_gender = $this->getLayout()->createBlock('customer/widget_gender');
			$_taxvat = $this->getLayout()->createBlock('customer/widget_taxvat');
			$customer =  Mage::getSingleton('customer/session')->getCustomer();
			$customerForm = Mage::getModel('customer/form');
			$customerForm->setFormCode('customer_account_edit')->setEntity($customer);
			if (isset($postData['dob'])) {
				$dob = Mage::app()->getLocale()->date($postData['dob'], null, null, false)->toString('yyyy-MM-dd');
				$customer->setDob($dob);
			}
			if (isset($postData['gender'])) {
				$gender = $postData['gender'];
				$customer->setGender($gender);
			}

			if (isset($postData['taxvat'])) {
				$taxvat = $postData['taxvat'];
				$customer->setTaxvat($taxvat);
			}


			if (isset($postData['suffix']) && $customer->getSuffix()=='' ) {
				$suffix =  $postData['suffix'];
				$customer->setSuffix($suffix);
			}

			if (isset($postData['prefix']) && $customer->getPrefix()=='' ) {
				$prefix =  $postData['prefix'];
				$customer->setPrefix($prefix);
			}
			if (isset($postData['middlename']) && $customer->getMiddlename()=='' ) {
				$middle =  $postData['middlename'];
				$customer->setMiddlename($middle);
			}

			if(isset($postData['tipopessoa'])){
				$tipopessoa = $postData['tipopessoa'];
			} else {
				$tipopessoa = "1";
			}

			if(isset($postData['cnpj'])){
				$cnpj = $postData['cnpj'];
			} else {
				$cnpj = "";
			}
				if(isset($postData['insestadual'])){
				$insestadual = $postData['insestadual'];
			} else {
				$insestadual = "";
			}
			if(isset($postData['nomefantasia'])){
				$nomefantasia = $postData['nomefantasia'];
			} else {
				$nomefantasia = "";
			}
			if(isset($postData['razaosocial'])){
				$razaosocial = $postData['razaosocial'];
			} else {
				$razaosocial = "";
			}
			

			
			$customer->setTipopessoa($tipopessoa)
					->setCnpj($cnpj)
					->setinsestadual($insestadual)
					->setNomefantasia($nomefantasia)
					->setRazaosocial($razaosocial);
			$customer->save();
			$this->saveAddress('billing', $data_save_billing);
		} else {
			$this->getOnepage()->getQuote()->setCheckoutMethod('register');
			$postData = $this->getRequest()->getPost('billing', array());
			$websiteId = Mage::app()->getWebsite()->getId();
			$store = Mage::app()->getStore();
			
			if (isset($postData['dob'])) {
				$dob = Mage::app()->getLocale()->date($postData['dob'], null, null, false)->toString('yyyy-MM-dd');
				$customer->setDob($dob);
			}
			if (isset($postData['gender'])) {
				$gender = $postData['gender'];
				$customer->setGender($gender);
			}

			if (isset($postData['taxvat'])) {
				$taxvat = $postData['taxvat'];
				$customer->setTaxvat($taxvat);
			}

			if(isset($postData['tipopessoa'])){
				$tipopessoa = $postData['tipopessoa'];
			} else {
				$tipopessoa = "1";
			}

			if(isset($postData['cnpj'])){
				$cnpj = $postData['cnpj'];
			} else {
				$cnpj = "";
			}
				if(isset($postData['insestadual'])){
				$insestadual = $postData['insestadual'];
			} else {
				$insestadual = "";
			}
			if(isset($postData['nomefantasia'])){
				$nomefantasia = $postData['nomefantasia'];
			} else {
				$nomefantasia = "";
			}
			if(isset($postData['razaosocial'])){
				$razaosocial = $postData['razaosocial'];
			} else {
				$razaosocial = "";
			}
			

			
			
			$customer = Mage::getModel("customer/customer");

			$customer ->setWebsiteId($websiteId)
			            ->setStore($store)
			            ->setFirstname($postData['firstname'])
			            ->setLastname($postData['lastname'])
			            ->setEmail($postData['email'])
						->setTipopessoa($tipopessoa)
						->setCnpj($cnpj)
						->setinsestadual($insestadual)
						->setNomefantasia($nomefantasia)
						->setRazaosocial($razaosocial)
						->setPassword($postData['confirm_password']);

			$customer->save();
			try{
					Mage::getSingleton('customer/session')->loginById($customer->getId());
			    	$this->saveAddress('billing', $data_save_billing);
			}
			catch (Exception $e) {
			    	$message = $e->getMessage();
					$json_response = array('erros' => 1, 'msg_error' => $message);
	    			$this->getResponse()->setBody(json_encode($json_response));
			}
		}

		$customerAddressId  = "";




		$result_save_billing = $this->getOnepage()->saveBilling($data_save_billing, $customerAddressId);

		$isclick=$this->getRequest()->getPost('ship_to_same_address');
		$ship="billing";
		if (!$isclick=='1') {
			$ship="shipping";
		}

		if ($this->getrequest()->ispost()) {
			$data_save_shipping = $this->getrequest()->getpost($ship, array());
			if ($this->isCustomerLoggedIn() && !$isclick) {
				$this->saveAddress('shipping', $data_save_shipping);
			}


			if ($isclick=='1') {
				$data_save_shipping['same_as_billing']=1;
			}
			$customeraddressid = $this->getrequest()->getpost($ship.'_address_id', false);
			if ($isclick || ($this->getRequest()->getPost('shipping_address_id') != "")) {
				$customeraddressid  = "";
			}
			$result_save_shipping = $this->getonepage()->saveshipping($data_save_shipping, $customeraddressid);
		}
		if ($this->getRequest()->isPost('shipping_method')) {
			$data_save_shipping_method = $this->getRequest()->getPost('shipping_method', '');
			$result_save_shipping_method = $this->getOnepage()->saveShippingMethod($data_save_shipping_method);
			if (!$result_save_shipping_method) {
				Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method', array('request'=>$this->getRequest(), 'quote'=>$this->getOnepage()->getQuote()));
				$this->getOnePage()->getQuote()->getShippingAddress()->setShippingMethod($data_save_shipping_method)->save();
			}
			try{
				$this->getOnePage()->getQuote()->getShippingAddress()->setShippingMethod($data_save_shipping_method)->save();
			} catch(Exception $e) {
				$message = $e->getMessage();
				$json_response = array('erros' => 1, 'msg_error' => $message);
    			$this->getResponse()->setBody(json_encode($json_response));
				return;
			}

		}

		$result_savepayment = array();
		$data_savepayment = $this->getRequest()->getPost('payment', array());
			try{
				$result_savepayment = $this->getOnepage()->savePayment($data_savepayment);
			} catch(Exception $e) {
				$message = $e->getMessage();
				$json_response = array('erros' => 1, 'msg_error' => $message);
    			$this->getResponse()->setBody(json_encode($json_response));

				return;
			}
		$redirectUrl = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
			if (isset($redirectUrl)) {
				$this->_redirectUrl($redirectUrl);
				return;
			}
		$result_order = array();
			if ($data_order = $this->getRequest()->getPost('payment', false)) {
				$this->getOnepage()->getQuote()->getPayment()->importData($data_order);
			}
			try{
				$this->getOnepage()->saveOrder();
				if ($this->getRequest()->getPost('subscribe_newsletter')=='1') {
						if ($this->isCustomerLoggedIn()) {
							$customer = Mage::getSingleton('customer/session')->getCustomer();
							$customer->setIsSubscribed(1);
							$this->savesubscibe($data_save_billing['email']);
						}else {
							$this->savesubscibe($data_save_billing['email']);
						}
				}

			}
			catch (Exception $e) {
				$message = $e->getMessage();
				$json_response = array('erros' => 1, 'msg_error' => $message);
    			$this->getResponse()->setBody(json_encode($json_response));
			}
		$session = $this->getOnepage()->getCheckout();
		$lastOrderId = $session->getLastOrderId();
		$data_customercomment ="";
		if ($this->getrequest()->ispost()) {
			$data_customercomment = $this->getrequest()->getpost('onestepcheckout_comments');
			$order=Mage::getModel('onestepcheckout/onestepcheckout');
			$order->setSalesOrderId($lastOrderId);
			$order->setMoipCustomercommentInfo($data_customercomment);

			$order->save();
		}
		$redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();
		$result_order['success'] = true;
		$result_order['error']   = false;
		$quote = Mage::getSingleton('checkout/session')->getQuote();
		 $allQuoteItems = $quote->getAllItems();
		    foreach ($allQuoteItems as $_item) {
		        $_product = $_item->getProduct();
		      if ($_product->getIsPreparedToDelete()) {
		          $quote->removeItem($_item->getId());
		       }
		    }
		    $quote->save();
		if (isset($redirectUrl)) {
				$convertQuote = Mage::getSingleton('sales/convert_quote');
    			$order = $convertQuote->toOrder($quote);
				$json_response = array('erros' => 0, 'msg_success' => $redirectUrl);
    			$this->getResponse()->setBody(json_encode($json_response));
    			
			
			
			return;
		}
		$convertQuote = Mage::getSingleton('sales/convert_quote');
		$order = $convertQuote->toOrder($quote);
		$json_response = array('erros' => 0, 'msg_success' => Mage::getUrl('checkout/onepage/success'));
    	$this->getResponse()->setBody(json_encode($json_response));
    	
			
		


	}


	protected function filterdata($data, $filter="true") {

		$arrayname=array('address_id', 'firstname', 'lastname', 'company', 'email', 'city', 'region_id', 'region', 'postcode', 'country_id', 'telephone', 'fax','tipopessoa','nomefantasia','razaosocial','cnpj','insestadual', 'save_in_address_book');
		$filterdata=array();
		if ($filter =='true') {
			if (version_compare(Mage::getVersion(), '1.4.2.0', '>=')) {
				$filterdata=array(
					'prefix'=>'n/a',
					'address_id'=>'n/a',
					'firstname'=>'n/a',
					'lastname'=>'n/a',
					'company'=>'n/a',
					'email'=>'n/a@na.na',
					'street'=>array('n/a', 'n/a', 'n/a', 'n/a'),
					'city'=>'n/a',
					'region_id'=>'n/a',
					'region'=>'n/a' ,
					'postcode'=>'',
					'country_id'=>'n/a',
					'telephone'=>'n/a',
					'fax'=>'n/a',
					'month'=> null,
					'day'=>null,
					'year'=>null,
					'dob'=>'01/01/1900',
					'gender'=>'n/a',
					'taxvat'=>'n/a',
					'suffix'=>'n/a',
					'tipopessoa'=>'n/a',
					'nomefantasia'=>'n/a',
					'razaosocial'=>'n/a',
					'cnpj'=>'n/a',
					'insestadual'=>'n/a',
					'save_in_address_book'=>''
				);
			}
			else {

				$filterdata=array(
					'prefix'=>'n/a',
					'address_id'=>'n/a',
					'firstname'=>'n/a',
					'lastname'=>'n/a',
					'company'=>'n/a',
					'email'=>'n/a',
					'street'=>array(
						'street1'=>'street is null',
						'street2'=>'street is null',
						'street3'=>'street is null',
						'street4'=>'street is null'
					),
					'city'=>'n/a',
					'region_id'=>'n/a',
					'region'=>'n/a' ,
					'postcode'=>'',
					'country_id'=>'n/a',
					'telephone'=>'n/a',
					'fax'=>'n/a',
					'month'=> null,
					'day'=>null,
					'year'=>null,
					'dob'=>'01/01/1900',
					'gender'=>'n/a',
					'taxvat'=>'n/a',
					'suffix'=>'n/a',
					'tipopessoa'=>'n/a',
					'nomefantasia'=>'n/a',
					'razaosocial'=>'n/a',
					'cnpj'=>'n/a',
					'insestadual'=>'n/a',
					'save_in_address_book'=>''
				) ;
			}
		}
		else {
			$filterdata=array(
				'prefix'=>'',
				'address_id'=>'',
				'firstname'=>'',
				'lastname'=>'',
				'company'=>'',
				'email'=>'',
				'street'=>array('', '', '', ''),
				'city'=>'',
				'region_id'=>'',
				'region'=>'' ,
				'postcode'=>'',
				'country_id'=>'',
				'telephone'=>'',
				'fax'=>'',
				'month'=> null,
				'day'=>null,
				'year'=>null,
				'dob'=>null,
				'gender'=>'',
				'taxvat'=>'',
				'suffix'=>'',
				'tipopessoa'=>'',
				'nomefantasia'=>'',
				'razaosocial'=>'',
				'cnpj'=>'',
				'insestadual'=>'',
				'save_in_address_book'=>''
			);
		}
		foreach ($data as $item=>$value) {
			if (!is_array($value)) {
				if ($value!='')
					$filterdata[$item]=$value;
			}
			else {
				$street=$value;

				if (isset($street[0])){

					if(isset($street[1]) || $street[1] =="."){
					$street_1 = $street[1];
					} else {
						$street_2 = "";
					}

					if(isset($street[2])){
					$street_2 = $street[2];
					} else {
						$street_2 = "";
					}
					if(isset($street[3])){
					$street_3 = $street[3];
					} else {
						$street_3 = "";
					}


						$filterdata[$item]=array($street[0], $street_1,$street_2,$street_3);
				}

			}
		}
		return $filterdata;
	}

	public function isCustomerLoggedIn() {
		return Mage::getSingleton('customer/session')->isLoggedIn();
	}


	public function savesubscibe($mail) {
		if ($mail) {
			
				$session            = Mage::getSingleton('checkout/session');
				$customerSession    = Mage::getSingleton('customer/session');
				$email              = (string) $mail;
				try {
					if (!Zend_Validate::is($email, 'EmailAddress')) {
						Mage::throwException($this->__('Please enter a valid email address.'));
					}
					if (Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_ALLOW_GUEST_SUBSCRIBE_FLAG) != 1 &&
						!$customerSession->isLoggedIn()) {
						Mage::throwException($this->__('Sorry, but administrator denied subscription for guests. Please <a href="%s">register</a>.', Mage::getUrl('customer/account/create/')));
					}
					$ownerId = Mage::getModel('customer/customer')
					->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
					->loadByEmail($email)
					->getId();
					if ($ownerId !== null && $ownerId != $customerSession->getId()) {
						Mage::throwException($this->__('Sorry, but your can not subscribe email adress assigned to another user.'));
					}
					$status = Mage::getModel('newsletter/subscriber')->subscribe($email);
					if ($status == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE) {
						$session->addSuccess($this->__('Confirmation request has been sent.'));
					}
					else {
						$session->addSuccess($this->__('Thank you for your subscription.'));
					}
				}
				catch (Mage_Core_Exception $e) {
					$message = $e->getMessage();
					$json_response = array('erros' => 1, 'msg_error' => $message);
	    			$this->getResponse()->setBody(json_encode($json_response));
				}
				catch (Exception $e) {
					$message = $e->getMessage();
					$json_response = array('erros' => 1, 'msg_error' => $message);
	    			$this->getResponse()->setBody(json_encode($json_response));
				}
			
		}

	}

	public function updatebillingformAction() {
		$this->updatebillingform();
	}

	public function updatesortbillingformAction() {
		$this->updatebillingform();
	}
	public function updateshippingformAction() {

		$this->updateshippingform();
	}

	public function updatesortshippingformAction() {
		$this->updateshippingform();
	}

	public function updateshippingform() {
		if ($this->getRequest()->isPost()) {
			$postData=$this->getRequest()->getPost();
			$customerAddressId = $postData['shipping_address_id'];
			if (intval($customerAddressId)!=0) {
				$postData = $this->filterdata($postData);
				if (version_compare(Mage::getVersion(), '1.4.0.1', '>='))
					$data = $this->_filterPostData($postData);
				else
					$data=$postData;
				$result = $this->getOnepage()->saveShipping($data, $customerAddressId);
			}
		}
		$this->loadLayout()->renderLayout();

	}


	public function updatebillingform() {
		if ($this->getRequest()->isPost()) {
			$billing_data = $this->getRequest()->getPost('billing', array());
			$postData=$this->getRequest()->getPost();
			if(isset($postData['billing_address_id'])){
				$billingAddressId = $postData['billing_address_id'];
			} else {
				$billingAddressId = Mage::getModel('customer/customer')->getPrimaryBillingAddress();
			}
			if (intval($billingAddressId)!=0) {
				$result = $this->getOnepage()->saveBilling($billing_data, $billingAddressId);
			}
			else {

			}
			$this->loadLayout()->renderLayout();
		}

	}

	public function editAction(){
		$data_save_billing = $this->getRequest()->getPost('billing', array());
		$data_save_billing['email'] = trim($data_save_billing['email']);
		
			$postData = $this->getRequest()->getPost('billing', array());
			$_dob = $this->getLayout()->createBlock('customer/widget_dob');
			$_gender = $this->getLayout()->createBlock('customer/widget_gender');
			$_taxvat = $this->getLayout()->createBlock('customer/widget_taxvat');
			$customer =  Mage::getSingleton('customer/session')->getCustomer();
			$customerForm = Mage::getModel('customer/form');
			$customerForm->setFormCode('customer_account_edit')->setEntity($customer);
			if (isset($postData['dob'])) {
				$dob = Mage::app()->getLocale()->date($postData['dob'], null, null, false)->toString('yyyy-MM-dd');
				$customer->setDob($dob);
			}
			if (isset($postData['gender'])) {
				$gender = $postData['gender'];
				$customer->setGender($gender);
			}

			if (isset($postData['taxvat'])) {
				$taxvat = $postData['taxvat'];
				$customer->setTaxvat($taxvat);
			}


			if (isset($postData['suffix']) && $customer->getSuffix()=='' ) {
				$suffix =  $postData['suffix'];
				$customer->setSuffix($suffix);
			}

			if (isset($postData['prefix']) && $customer->getPrefix()=='' ) {
				$prefix =  $postData['prefix'];
				$customer->setPrefix($prefix);
			}
			if (isset($postData['middlename']) && $customer->getMiddlename()=='' ) {
				$middle =  $postData['middlename'];
				$customer->setMiddlename($middle);
			}
			if(isset($postData['tipopessoa'])){
				$customer->setTipopessoa($postData['tipopessoa'])
						->setRazaosocial($postData['razaosocial'])
						->setNomefantasia($postData['nomefantasia'])
						->setcnpj($postData['cnpj'])
						->setinsestadual($postData['insestadual']);
			}
			$customer->save();
			try {
				
	            $address  = Mage::getModel('customer/address');
	            $addressId = $postData['address_id'];
	            if ($addressId) {
	                $existsAddress = $customer->getAddressById($addressId);
	                if ($existsAddress->getId() && $existsAddress->getCustomerId() == $customer->getId()) {
	                    $address->setId($existsAddress->getId());
	                }
	            }

	            $errors = array();

	            
	            $addressForm = Mage::getModel('customer/form');
	            $addressForm->setFormCode('customer_address_edit')
	                ->setEntity($address);
	            $addressData    = $postData;
	            $addressErrors  = $addressForm->validateData($addressData);
	            if ($addressErrors !== true) {
	                $errors = $addressErrors;
	            }

	            try {
	                $addressForm->compactData($addressData);
	                $address->setCustomerId($customer->getId())
	                    ->setIsDefaultBilling(1)
	                    ->setIsDefaultShipping(1);

	                $addressErrors = $address->validate();
	                if ($addressErrors !== true) {
	                    $errors = array_merge($errors, $addressErrors);
	                }

	                if (count($errors) === 0) {
	                    $address->save();
	                    
	                    $this->_redirectSuccess(Mage::getUrl('checkout/onepage', array('_secure'=>true)));
	                    return;
	                } else {
	                  
	                    foreach ($errors as $errorMessage) {
	                        Mage::getSingleton('core/session')->addError($errorMessage);
	                    }
	                }
	            } catch (Mage_Core_Exception $e) {
	                $this->_getSession()->setAddressFormData($this->getRequest()->getPost())
	                    ->addException($e, $e->getMessage());
	            } 
	      

			} catch (Mage_Core_Exception $e) {
				$message = $e->getMessage();
				$json_response = array('erros' => 1, 'msg_error' => $message);
				$this->getResponse()->setBody(json_encode($json_response));
			}
			
		
			
	}



	public function saveaddessAction(){
		$data_save_billing = $this->getRequest()->getPost('billing', array());
		$data_save_billing['email'] = trim($data_save_billing['email']);
		
			$postData = $this->getRequest()->getPost('billing', array());
			$_dob = $this->getLayout()->createBlock('customer/widget_dob');
			$_gender = $this->getLayout()->createBlock('customer/widget_gender');
			$_taxvat = $this->getLayout()->createBlock('customer/widget_taxvat');
			$customer =  Mage::getSingleton('customer/session')->getCustomer();
			$customerForm = Mage::getModel('customer/form');
			$customerForm->setFormCode('customer_account_edit')->setEntity($customer);
			if (isset($postData['dob'])) {
				$dob = Mage::app()->getLocale()->date($postData['dob'], null, null, false)->toString('yyyy-MM-dd');
				$customer->setDob($dob);
			}
			if (isset($postData['gender'])) {
				$gender = $postData['gender'];
				$customer->setGender($gender);
			}

			if (isset($postData['taxvat'])) {
				$taxvat = $postData['taxvat'];
				$customer->setTaxvat($taxvat);
			}


			if (isset($postData['suffix']) && $customer->getSuffix()=='' ) {
				$suffix =  $postData['suffix'];
				$customer->setSuffix($suffix);
			}

			if (isset($postData['prefix']) && $customer->getPrefix()=='' ) {
				$prefix =  $postData['prefix'];
				$customer->setPrefix($prefix);
			}
			if (isset($postData['middlename']) && $customer->getMiddlename()=='' ) {
				$middle =  $postData['middlename'];
				$customer->setMiddlename($middle);
			}
			if(isset($postData['tipopessoa'])){
				$customer->setTipopessoa($postData['tipopessoa'])
						->setRazaosocial($postData['razaosocial'])
						->setNomefantasia($postData['nomefantasia'])
						->setcnpj($postData['cnpj'])
						->setinsestadual($postData['insestadual']);
			}
			$customer->save();
			try {
				
				$address  = Mage::getModel('customer/address');
				$errors = array();
				$addressForm = Mage::getModel('customer/form');
				$addressForm->setFormCode('customer_address_edit')->setEntity($address);
				$addressData    = $this->getRequest()->getPost('billing', array());
				try {
					$addressForm->compactData($addressData);
					$address->setCustomerId($customer->getId())
					->setIsDefaultBilling(1)
					->setIsDefaultShipping(1)->setSaveInAddressBook('1');
					$address->save();
					$json_response = array('success' => 1);
					$this->getResponse()->setBody(json_encode($json_response));
				} catch (Mage_Core_Exception $e) {
					$message = $e->getMessage();
					$json_response = array('success' => 0, 'msg_error' => $message);
					$this->getResponse()->setBody(json_encode($json_response));
				}

			} catch (Mage_Core_Exception $e) {
				$message = $e->getMessage();
				$json_response = array('erros' => 1, 'msg_error' => $message);
				$this->getResponse()->setBody(json_encode($json_response));
			}
			
		
			
	}
	
	public function saveAddress($type,$data){

 	$addressId = $this->getRequest()->getPost($type.'_address_id');

	 	if(isset($data['save_in_address_book'])){
	 		$save_address = $data['save_in_address_book'];
	 		$customer = Mage::getSingleton('customer/session')->getCustomer();
			$address  = Mage::getModel('customer/address');
			$errors = array();
			$addressForm = Mage::getModel('customer/form');
			$addressForm->setFormCode('customer_address_edit')->setEntity($address);
			$addressData    = $this->getRequest()->getPost($type, array());
			try {
				$addressForm->compactData($addressData);
				$address->setCustomerId($customer->getId())
				->setIsDefaultBilling(1)
				->setIsDefaultShipping(1)->setSaveInAddressBook('1');
				$address->save();
				return $this;
			} catch (Mage_Core_Exception $e) {
				$message = $e->getMessage();
				$json_response = array('erros' => 1, 'msg_error' => $message);
				$this->getResponse()->setBody(json_encode($json_response));
			}
	 	}
	}
}
