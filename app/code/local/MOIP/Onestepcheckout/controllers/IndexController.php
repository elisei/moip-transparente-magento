<?php
require_once 'Mage/Checkout/controllers/OnepageController.php';
class MOIP_Onestepcheckout_IndexController extends Mage_Checkout_OnepageController
{ 
	
	const XML_CSRF_USE_FLAG_CONFIG_PATH   = 'system/csrf/use_form_key';
    
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

	protected function _prepareDataJSON($response)
    {
        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
    }
	
    protected function _validateFormKey()
    {
        $validated = true;
        if ($this->_isFormKeyEnabled()) {
            $validated = parent::_validateFormKey();
        }
        return $validated;
    }

    protected function _isFormKeyEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_CSRF_USE_FLAG_CONFIG_PATH);
    }

     protected function isFormkeyValidationOnCheckoutEnabled()
    {
        return Mage::getStoreConfigFlag('admin/security/validate_formkey_checkout');
    }

    public function cadastroAction() {

        $this->_initLayoutMessages('checkout/session');
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

    protected function _escapeHtml($text)
    {
        return Mage::helper('core')->escapeHtml($text);
    }

    protected function _getCustomerErrors($customer)
    {
        $errors = array();
        $request = $this->getRequest();
        if ($request->getPost('create_address')) {
            $errors = $this->_getErrorsOnCustomerAddress($customer);
        }
        $customerForm = $this->_getCustomerForm($customer);
        $customerData = $customerForm->extractData($request);
        $customerErrors = $customerForm->validateData($customerData);
        if ($customerErrors !== true) {
            $errors = array_merge($customerErrors, $errors);
        } else {
            $customerForm->compactData($customerData);
            $customer->setPassword($request->getPost('password'));
            $customer->setPasswordConfirmation($request->getPost('confirmation'));
            $customerErrors = $customer->validate();
            if (is_array($customerErrors)) {
                $errors = array_merge($customerErrors, $errors);
            }
        }
        return $errors;
    }

    protected function _getErrorsOnCustomerAddress($customer)
    {
        $errors = array();
        /* @var $address Mage_Customer_Model_Address */
        $address = $this->_getModel('customer/address');
        /* @var $addressForm Mage_Customer_Model_Form */
        $addressForm = $this->_getModel('customer/form');
        $addressForm->setFormCode('customer_register_address')
            ->setEntity($address);

        $addressData = $addressForm->extractData($this->getRequest(), 'address', false);
        $addressErrors = $addressForm->validateData($addressData);
        if (is_array($addressErrors)) {
            $errors = array_merge($errors, $addressErrors);
        }
        $address->setId(null)
            ->setIsDefaultBilling($this->getRequest()->getParam('default_billing', false))
            ->setIsDefaultShipping($this->getRequest()->getParam('default_shipping', false));
        $addressForm->compactData($addressData);
        $customer->addAddress($address);

        $addressErrors = $address->validate();
        if (is_array($addressErrors)) {
            $errors = array_merge($errors, $addressErrors);
        }
        return $errors;
    }


    protected function _getCustomerForm($customer)
    {
        $customerForm = $this->_getModel('customer/form');
        $customerForm->setFormCode('customer_account_create');
        $customerForm->setEntity($customer);
        return $customerForm;
    }

    protected function _getFromRegistry($path)
    {
        return Mage::registry($path);
    }

    public function _getModel($path, $arguments = array())
    {
        return Mage::getModel($path, $arguments);
    }

    protected function _getHelper($path)
    {
        return Mage::helper($path);
    }

    protected function _getCustomer()
    {
        $customer = $this->_getFromRegistry('current_customer');
        if (!$customer) {
            $customer = $this->_getModel('customer/customer')->setId(null);
        }
        if ($this->getRequest()->getParam('is_subscribed', false)) {
            $customer->setIsSubscribed(1);
        }
        $customer->getGroupId();

        return $customer;
    }

    protected function _loginPostRedirect()
    {
        $session = $this->_getSession();

        if (!$session->getBeforeAuthUrl() || $session->getBeforeAuthUrl() == Mage::getBaseUrl()) {
            // Set default URL to redirect customer to
            $session->setBeforeAuthUrl($this->_getHelper('customer')->getAccountUrl());
            // Redirect customer to the last page visited after logging in
            if ($session->isLoggedIn()) {
                if (!Mage::getStoreConfigFlag(
                    Mage_Customer_Helper_Data::XML_PATH_CUSTOMER_STARTUP_REDIRECT_TO_DASHBOARD
                )) {
                    $referer = $this->getRequest()->getParam(Mage_Customer_Helper_Data::REFERER_QUERY_PARAM_NAME);
                    if ($referer) {
                        // Rebuild referer URL to handle the case when SID was changed
                        $referer = $this->_getModel('core/url')
                            ->getRebuiltUrl( $this->_getHelper('core')->urlDecodeAndEscape($referer));
                        if ($this->_isUrlInternal($referer)) {
                            $session->setBeforeAuthUrl($referer);
                        }
                    }
                } else if ($session->getAfterAuthUrl()) {
                    $session->setBeforeAuthUrl($session->getAfterAuthUrl(true));
                }
            } else {
                $session->setBeforeAuthUrl( $this->_getHelper('customer')->getLoginUrl());
            }
        } else if ($session->getBeforeAuthUrl() ==  $this->_getHelper('customer')->getLogoutUrl()) {
            $session->setBeforeAuthUrl( $this->_getHelper('customer')->getDashboardUrl());
        } else {
            if (!$session->getAfterAuthUrl()) {
                $session->setAfterAuthUrl($session->getBeforeAuthUrl());
            }
            if ($session->isLoggedIn()) {
                $session->setBeforeAuthUrl($session->getAfterAuthUrl(true));
            }
        }
        $this->_redirectUrl($session->getBeforeAuthUrl(true));
    }

    public function formPostCreateAction()
    {

    	$errUrl = Mage::getUrl('*/*/', array('_secure' => true));

        if (!$this->_validateFormKey()) {
             $this->_redirect('*/*/');
            return;
        }

        /** @var $session Mage_Customer_Model_Session */
        $session = $this->_getSession();
        if ($session->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }

        if (!$this->getRequest()->isPost()) {
             $this->_redirect($errUrl);
            return;
        }

        $customer = $this->_getCustomer();

        try {
            $errors = $this->_getCustomerErrors($customer);

            if (empty($errors)) {
                $customer->cleanPasswordsValidationData();
                $customer->setPasswordCreatedAt(time());
                $customer->save();
                $this->_dispatchRegisterSuccess($customer);
                $this->_successProcessRegistration($customer);
                return;
            } else {
                $this->_addSessionError($errors);
            }
        } catch (Mage_Core_Exception $e) {
            $session->setCustomerFormData($this->getRequest()->getPost());
            if ($e->getCode() === Mage_Customer_Model_Customer::EXCEPTION_EMAIL_EXISTS) {
                $url = $this->_getUrl('customer/account/forgotpassword');
                $message = $this->__('There is already an account with this email address. If you are sure that it is your email address, <a href="%s">click here</a> to get your password and access your account.', $url);
            } else {
                $message = $this->_escapeHtml($e->getMessage());
            }
            $session->addError($message);
        } catch (Exception $e) {
            $session->setCustomerFormData($this->getRequest()->getPost());
            $session->addException($e, $this->__('Cannot save the customer.'));
        }

        $this->_loginPostRedirect();
        return $this;
    }

    protected function _addSessionError($errors)
    {
        $session = $this->getSession();
        $session->setCustomerFormData($this->getRequest()->getPost());
        if (is_array($errors)) {
            foreach ($errors as $errorMessage) {
                $session->addError($this->_escapeHtml($errorMessage));
            }
        } else {
            $session->addError($this->__('Invalid customer data'));
        }
    }

    protected function _successProcessRegistration(Mage_Customer_Model_Customer $customer)
    {
        $session = $this->_getSession();
        if ($customer->isConfirmationRequired()) {
            /** @var $app Mage_Core_Model_App */
            $app = $this->_getApp();
            /** @var $store  Mage_Core_Model_Store*/
            $store = $app->getStore();
            $customer->sendNewAccountEmail(
                'confirmation',
                $session->getBeforeAuthUrl(),
                $store->getId(),
                $this->getRequest()->getPost('password')
            );
            $customerHelper = $this->_getHelper('customer');
            $session->addSuccess($this->__('Account confirmation is required. Please, check your email for the confirmation link. To resend the confirmation email please <a href="%s">click here</a>.',
                $customerHelper->getEmailConfirmationUrl($customer->getEmail())));
            $url = $this->_getUrl('*/*/index', array('_secure' => true));
        } else {
            $session->setCustomerAsLoggedIn($customer);
        }
        $this->_redirect('checkout/onepage/');
        return $this;
    }

    public function ClearIdentifyUserAction(){
        $session = $this->getSession();
        $session->setStep('');
        $this->_redirect('*/*/');
        return $this;
    }
    public function IdentifyUserAction(){
        if ($this->isCustomerLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }
        if (!$this->_validateFormKey()) {
            $this->_redirect('*/*/');
            return;
        }

        $email = (string) $this->getRequest()->getPost('email','');
        $websiteid=Mage::app()->getWebsite()->getId();
        $store=Mage::app()->getStore();
        $customer=Mage::getSingleton('customer/customer');
        $customer->website_id=$websiteid;
        $customer->setStore($store);
        $customer->loadByEmail($email);
        if ($customer->getId()) {
            $session = $this->getSession();

            $_session = $this->_getSession();
            $_session->setUsername($email);
            $_session->setEmail($email);
            $session->setStep('login');
            $output = $this->getLayout()->createBlock('checkout/onepage_login')->setBlockId('checkout.onepage.login')->setTemplate('MOIP/onestepcheckout/cadastro/logged/login-pre.phtml');
            $output = $output->toHtml();
            $result = $result = array("success" => true, "is_user" => true, "html" => $output);
        } else {
            $session = $this->getSession();
            $_session = $this->_getSession();
            $_session->setUsername($email);
            $session->setStep('signup');
            $_session = $this->_getSession();
            $_session->setCustomerFormData($this->getRequest()->getPost());
            $output = $this->getLayout()->createBlock('onestepcheckout/checkout_address_edit')->setBlockId('checkout.onepage.address');

            $block_links1 = $this->getLayout()->createBlock('checkout/agreements','agreements')->setTemplate('MOIP/onestepcheckout/checkout/daskboard/onepage/agreements.phtml');

            $output->setChild('agreements',$block_links1);
            
            $output->setTemplate('MOIP/onestepcheckout/address/cadastro/form.phtml');
            
            $result = array("success" => true, "is_user" => false, "html" => $output->toHtml());
        }
        $this->_prepareDataJSON($result);
        return $this;
    }

    public function authenticateAction() {
        
        if ($this->isCustomerLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }
        if (!$this->_validateFormKey()) {
            $this->_redirect('*/*/');
            return;
        }

        $session = $this->_getSession();

        if ($this->getRequest()->isPost()) {
            $login = $this->getRequest()->getPost('login');
            if (!empty($login['username']) && !empty($login['password'])) {
                try {
                    $session->login($login['username'], $login['password']);
                    $result = array("success" => true, 'redirect' => Mage::getUrl('checkout/onepage/', array('_secure'=>true)));
                } catch (Mage_Core_Exception $e) {
                    switch ($e->getCode()) {
                        case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                            $value = $this->_getHelper('customer')->getEmailConfirmationUrl($login['username']);
                            $message = $this->_getHelper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
                            break;
                        case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                            $message = $e->getMessage();
                            break;
                        default:
                            $message = $e->getMessage();
                    }
                    $result = array("success" => false, "error" => $message);
                    $session->setUsername($login['username']);
                } catch (Exception $e) {
                    // Mage::logException($e); // PA DSS violation: this exception log can disclose customer password
                }
            } else {
                $message = $this->__('Login and password are required.');
                $result = array("success" => false, "error" => $message);
            }
        }

        $this->_prepareDataJSON($result);
        return $this;
    }













    //actions de checkout





    public function renderTotals(){
        $this->getOnepage()->getQuote()->collectTotals()->save();
        $output = $this->getLayout()->createBlock('checkout/cart_totals')->setBlockId('checkout.onepage.totals');
        $output->setTemplate('MOIP/onestepcheckout/checkout/daskboard/onepage/review/totals.phtml');
        return $output->toHtml();
    }

    public function NewAddressAction(){

        $output = $this->getLayout()->createBlock('onestepcheckout/checkout_address_edit')->setBlockId('checkout.onepage.address');
        if($typeform = $this->getRequest()->getParam('typeform')){
            $output->setData("typeform", $typeform);
        }
        
        $output->setTemplate('MOIP/onestepcheckout/address/checkout/form.phtml');
        $result = array('success'=>true,'html' => $output->toHtml());
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        return $this;

    }

    public function saveShippingAction()
    {
        if ($this->_expireAjax()) {
            return;
        }

        if ($this->isFormkeyValidationOnCheckoutEnabled() && !$this->_validateFormKey()) {
            return;
        }

        if ($this->getRequest()->isPost()) {
           /* $data = $this->getRequest()->getPost('shipping', array());*/
            $customerAddressId = $this->getRequest()->getPost('shipping_address_id', false);
            $data = Mage::getModel('customer/address')->load($customerAddressId);
            $data->setIsDefaultShipping(true);
            $data->save();
            $result = $this->getOnepage()->saveShipping($data, $customerAddressId);
            $this->getOnepage()->getQuote()->collectTotals()->save();
            if (!isset($result['error'])) {
                $output = $this->getLayout()->createBlock('checkout/onepage_shipping_method_available')->setBlockId('checkout.onepage.shipping_method.available');
                $output->setTemplate('MOIP/onestepcheckout/checkout/daskboard/onepage/shipping_method/available.phtml');
                $result = array(
                    'success' => true,
                    'html' => $output->toHtml(),
                    'totals' => $this->renderTotals()
                );
            }
            $this->_prepareDataJSON($result);
        }
    }

    public function saveShippingMethodAction()
    {
        if ($this->_expireAjax()) {
            return;
        }

        if ($this->isFormkeyValidationOnCheckoutEnabled() && !$this->_validateFormKey()) {
            return;
        }

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost('shipping_method', '');
            $method = $this->getOnepage()->saveShippingMethod($data);
            if (!$method) {
                Mage::dispatchEvent(
                    'checkout_controller_onepage_save_shipping_method',
                     array(
                          'request' => $this->getRequest(),
                          'quote'   => $this->getOnepage()->getQuote()));
                $this->getOnepage()->getQuote()->collectTotals();
               
               
                $output = $this->getLayout()->createBlock('checkout/onepage_payment_methods')->setBlockId('checkout.payment.methods');
                $output->setTemplate('MOIP/onestepcheckout/checkout/daskboard/onepage/payment/methods.phtml');
                $result = array(
                    'success' => true,
                    'html' => $output->toHtml(),
                    'totals' => $this->renderTotals()
                );
            }
            $this->getOnepage()->getQuote()->collectTotals()->save();
            $this->_prepareDataJSON($result);
        }
    }

    public function saveBillingAction()
    {
        if ($this->_expireAjax()) {
            return;
        }

        if ($this->isFormkeyValidationOnCheckoutEnabled() && !$this->_validateFormKey()) {
            return;
        }

        if ($this->getRequest()->isPost()) {
            $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);
            $data = Mage::getModel('customer/address')->load($customerAddressId);
            $result = $this->getOnepage()->saveBilling($data->getData(''), $customerAddressId);

            $output = $this->getLayout()->createBlock('checkout/onepage_payment_methods')->setBlockId('checkout.payment.methods');
            $output->setTemplate('MOIP/onestepcheckout/checkout/daskboard/onepage/payment/methods.phtml');
            $result = array(
                'success' => true,
                'update' => "billing",
                'html' => $output->toHtml(),
                'totals' => $this->renderTotals(),
            );
            $this->_prepareDataJSON($result);
        }
    }

    public function savePaymentMethodAction(){

        if ($this->_expireAjax()) {
            return;
        }

        if ($this->isFormkeyValidationOnCheckoutEnabled() && !$this->_validateFormKey()) {
            return;
        }

        $data = $this->getRequest()->getPost('payment', array());
        $quote =  $this->getOnepage()->getQuote();
        $methodCode = $data['method'];

        $setCode = $quote->getPayment()->setMethod(isset($data['method']) ? $data['method'] : null)->save();

        if ($quote->isVirtual()) {
            $quote->getBillingAddress()->setPaymentMethod(isset($data['method']) ? $data['method'] : null);
        } else {
            $quote->getShippingAddress()->setPaymentMethod(isset($data['method']) ? $data['method'] : null);
        }
        if (!$quote->isVirtual() && $quote->getShippingAddress()) {
            $quote->getShippingAddress()->setCollectShippingRates(true);
        }
        $this->getOnepage()->getQuote()->collectTotals()->save();
        $quote->save();
        $method = $quote->getPayment()->getMethodInstance();
        $method->assignData($data);
        //atenção não passo pelo valide aqui so no import date do save order!
        
        try {
           $result = array("success" => true,  "html" => $this->renderTotals());
        } catch (Exception $e) {
            $result = array("success" => false,  "error" => $e);
        }

        $this->_prepareDataJSON($result);
        return $this;
    }
	public function formPostAddressAction()
    {
        if (!$this->_validateFormKey()) {
            return $this->_redirect('*/*/');
        }
        // Save data
        if ($this->getRequest()->isPost()) {
            $customer = $this->_getSession()->getCustomer();
            /* @var $address Mage_Customer_Model_Address */
            $address  = Mage::getModel('customer/address');
            $addressId = $this->getRequest()->getParam('id');
            if ($addressId) {
                $existsAddress = $customer->getAddressById($addressId);
                if ($existsAddress->getId() && $existsAddress->getCustomerId() == $customer->getId()) {
                    $address->setId($existsAddress->getId());
                }
            }
            $result = array();
            $errors = array();

            /* @var $addressForm Mage_Customer_Model_Form */
            $addressForm = Mage::getModel('customer/form');
            $addressForm->setFormCode('customer_address_edit')
                ->setEntity($address);
            $addressData    = $addressForm->extractData($this->getRequest());
            $addressErrors  = $addressForm->validateData($addressData);

            if ($addressErrors !== true) {
                $errors = $addressErrors;
            }

            try {
                $addressForm->compactData($addressData);
                $address->setCustomerId($customer->getId())
                    ->setIsDefaultBilling($this->getRequest()->getParam('default_billing', false))
                    ->setIsDefaultShipping($this->getRequest()->getParam('default_shipping', false));

                $addressErrors = $address->validate();
                if ($addressErrors !== true) {
                    $errors = array_merge($errors, $addressErrors);
                }
                
                $update = $this->getRequest()->getPost('_form_type');
                if (count($errors) === 0) {
                    $address->save();
                   	try {
                   		if($update == "billing"){
                   			$customerAddressId = $address->getId();
				            $data = Mage::getModel('customer/address')->load($customerAddressId);
				            $result = $this->getOnepage()->saveBilling($address->getData(''), $customerAddressId);
	        				$output = $this->getLayout()
	        					->createBlock('onestepcheckout/checkout_onepage_billing')
	        					->setBlockId('checkout.onepage.billing')
	        					->setTemplate('MOIP/onestepcheckout/checkout/daskboard/onepage/billing.phtml')
	                    		->toHtml();
                            $result = array("success" => true, "update" => "shipping", "html" => $output);
	        			}
	                    
	                    elseif($update == "shipping"){
	                    	$customerAddressId = $address->getId();
			            	$data = Mage::getModel('customer/address')->load($customerAddressId);
			            	$result = $this->getOnepage()->saveShipping($data, $customerAddressId);
	        				$output = $this->getLayout()
	        					->createBlock('onestepcheckout/checkout_onepage_shipping')
	        					->setBlockId('checkout.onepage.shipping')
	        					->setCacheLifetime(null)
	                    		->setTemplate('MOIP/onestepcheckout/checkout/daskboard/onepage/shipping.phtml')
	                    		->toHtml();
                            $result = array("success" => true, "update" => "shipping", "html" => $output);
	        			} elseif($update == "edit"){
                            $result = array("success" => true, "update" => false, "redirect" => Mage::getUrl("checkout/onepage/"));
	        			} else {
                             $result = array("success" => true);
                        }
                        $this->_prepareDataJSON($result);
                       

                        return $this;
	                   
                   	} catch (Exception $e) {
                   		$result = array("success" => false, "update" => $update, "html" => $e);
                   	}

                    $this->_prepareDataJSON($result);
                    

                    return $this;
                } else {
                    $this->getSession()->setAddressFormData($this->getRequest()->getPost());
                    foreach ($errors as $errorMessage) {
                       $error .= $errorMessage;
                    }

                    $result = array("success" => false, "update" => "billing", "error" => $error);
                    $this->_prepareDataJSON($result);
                    return $this;
                    //* add erro resposta no ajax...

                }
            } catch (Mage_Core_Exception $e) {
                $this->getSession()->setAddressFormData($this->getRequest()->getPost())
                    ->addException($e, $e->getMessage());
                 $result = array("success" => false, "update" => "billing", "error" => $e->getMessage());
                 $this->_prepareDataJSON($result);
                return $this;
                // erro grave nem continuar so printar...
            } catch (Exception $e) {
                $this->getSession()->setAddressFormData($this->getRequest()->getPost())
                    ->addException($e, $this->__('Cannot save address.'));
                $result = array("success" => false, "update" => "billing", "error" => $this->__('Cannot save address.'));
                $this->_prepareDataJSON($result);
                return $this;

            }
        }

        return $this->_redirectError(Mage::getUrl('*/*/edit', array('id' => $address->getId())));
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

		
		$this->getOnepage()->initCheckout();

		if ($quote->isVirtual()) {
			$this->initshippingmethod();
		}
		$this->initpaymentmethod();
		
		$this->loadLayout();
		
		$this->getLayout()->getBlock('head')->setTitle($this->__('Checkout'));

		return true;
	}

	public function initshippingmethod() {
		$code = $this->getQuote()->getShippingAddress()->getShippingMethod();
		$result = $this->getOnepage()->saveShippingMethod($code);
        if (!$result) {
            Mage::dispatchEvent(
                'checkout_controller_onepage_save_shipping_method',
                 array(
                      'request' => $this->getRequest(),
                      'quote'   => $this->getOnepage()->getQuote()));
            $this->getOnepage()->getQuote()->collectTotals();
        }
        $this->getOnepage()->getQuote()->collectTotals()->save();
		return $this;
	}
	
	
	public function initpaymentmethod() {
		$quote = Mage::getSingleton('checkout/session')->getQuote();
		$quote =  $this->getOnepage()->getQuote();
		if ($quote->isVirtual()) {
		    $quote->getBillingAddress()->setPaymentMethod(isset($data['method']) ? $data['method'] : 'noob');
		} else {
			$quote->getShippingAddress()->setPaymentMethod(isset($data['method']) ? $data['method'] : 'noob');
		}
		$quote->save();
	}

	public function initInfoaddress() {
		$quote = $this->getOnepage()->getQuote();
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
		if (Mage::getSingleton('customer/session')->isLoggedIn() && !$quote->isVirtual()) {
			$customerAddressId = Mage::getSingleton('customer/session')->getCustomer()->getDefaultShipping();
		} else {
			$customerAddressId = 0;
		}

		$postData = $this->filterdata($postData);
		if (version_compare(Mage::getVersion(), '1.4.0.1', '>=')){
				if (isset($postData['email']))
					$postData['email'] = trim($postData['email']);
				$data = $this->_filterPostData($postData);
		} else{
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
                $result = array('status' => 'success'); 
                return $this->_prepareDataJSON($result);
			} catch (Exception $e) {
				$result = array('status' => 'fail'); 
                return $this->_prepareDataJSON($result);
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
        try {
            $result = array('status' => 'success');    
        }  catch (Exception $e) {
                $result = array('status' => 'fail'); 
                return $this->_prepareDataJSON($result);
            }
        return $this->_prepareDataJSON($result);
	}

	public function updatecouponAction() {
		$this->_initLayoutMessages('checkout/session');
		
        $remove     = (string) $this->getRequest()->getParam('remove_cupom');
        if(!$remove){
            $couponCode = (string) $this->getRequest()->getParam('coupon_code');    
        } 

		Mage::getSingleton('checkout/session')
		    ->getQuote()
		    ->setCouponCode(strlen($couponCode) ? $couponCode : '')
		    ->collectTotals()
		    ->save();
        try {
            $json_array = array('status' => 'success');    
        }  catch (Exception $e) {
                $result = array('status' => 'fail'); 
                return $this->_prepareDataJSON($result);
            }
		

		return $this->_prepareDataJSON($json_array);

	}

	

	public function _getSession() {
        return Mage::getSingleton('customer/session');
    }

    public function getSession() {
        return Mage::getSingleton('checkout/session');
    }

	

	/**
     * Dispatch Event
     *
     * @param Mage_Customer_Model_Customer $customer
     */
    protected function _dispatchRegisterSuccess($customer)
    {
        Mage::dispatchEvent('customer_register_success',
            array('account_controller' => $this, 'customer' => $customer)
        );
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
		parent::saveOrderAction();
		return $this;
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



	
}
