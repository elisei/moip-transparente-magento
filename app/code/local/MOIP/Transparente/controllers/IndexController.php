<?php
require_once 'Mage/Checkout/controllers/CartController.php';
class MOIP_Transparente_IndexController extends Mage_Checkout_CartController
{
	
	
	public function CartoesAction() {
		$this->loadLayout();
        $this->renderLayout();
	}
	public function RemoveAction() {
		$this->loadLayout();
        
		if($this->getRequest()->getParams()){
			$data = $this->getRequest()->getParams();
			$model = Mage::getModel('transparente/write');
			$model->load($data['cofre_remove'], 'moip_card_id');
			$model->setMoipCardId();
			$model->save();
			return true;
		}
	}
	

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
	public function renderLogin() {

		return  $this->getLayout()->getBlock('moip.oneclickbuy.login')->toHtml();
	}

	public function renderPayment() {
		$this->loadLayout();
		return  $this->getLayout()->getBlock('moip.oneclickbuy')->toHtml();
	}

	public function addAction()
	{
		$cart   = $this->_getCart();
		$params = $this->getRequest()->getParams();
		if($params['isAjax'] == 1){
			$response = array();
			try {
				if (isset($params['qty'])) {
					$filter = new Zend_Filter_LocalizedToNormalized(
					array('locale' => Mage::app()->getLocale()->getLocaleCode())
					);
					$params['qty'] = $filter->filter($params['qty']);
				}

				$product = $this->_initProduct();
				$related = $this->getRequest()->getParam('related_product');

				/**
				 * Check product availability
				 */
				if (!$product) {
					$response['status'] = 'ERROR';
					$response['message'] = $this->__('<h4 class="modal-title">Desculpe, função desabilitada, use o botão comprar</h4>');
				}

				$cart->addProduct($product, $params);
				if (!empty($related)) {
					$cart->addProductsByIds(explode(',', $related));
				}

				$cart->save();

				$this->_getSession()->setCartWasUpdated(true);

				/**
				 * @todo remove wishlist observer processAddToCart
				 */
				Mage::dispatchEvent('checkout_cart_add_product_complete',
				array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse())
				);

				if (!$cart->getQuote()->getHasError()){
					$message = $this->__('<h4 class="modal-title">Produto Adcionado ao Carrinho</h4>');
					$response['status'] = 'SUCCESS';
					$response['message'] = $message;
					$count = Mage::helper('checkout/cart')->getSummaryCount();
					$response['counttop'] = $count;
					//New Code Here
					Mage::register('referrer_url', $this->_getRefererUrl());
				
				}
			} catch (Mage_Core_Exception $e) {
				$msg = "";
				if ($this->_getSession()->getUseNotice(true)) {
					$msg = $e->getMessage();
				} else {
					$messages = array_unique(explode("\n", $e->getMessage()));
					foreach ($messages as $message) {
						$msg .= $message.'<br/>';
					}
				}

				$response['status'] = 'ERROR';
				$response['message'] = $msg;
			} catch (Exception $e) {
				$response['status'] = 'ERROR';
				$response['message'] = $this->__('<h4 class="modal-title">Não foi possível adcionar ao carrinho</h4>');
				Mage::logException($e);
			}
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
			return;
		}else{
			return parent::addAction();
		}
	}
	

	public function addOneclickbuyAction()
	{
		$cart   = $this->_getCart();
		$params = $this->getRequest()->getParams();
		if($params['oneclickbuy'] == 1){
			$response = array();
			try {
				if (isset($params['qty'])) {
					$filter = new Zend_Filter_LocalizedToNormalized(
					array('locale' => Mage::app()->getLocale()->getLocaleCode())
					);
					$params['qty'] = $filter->filter($params['qty']);
				}

				$product = $this->_initProduct();
				$related = $this->getRequest()->getParam('related_product');

				
				if (!$product) {
					$response['_status'] = 'ERROR';
					$response['message'] = $this->__('<h4 class="modal-title">Unable to find Product ID</h4>');
				}

				$cart->addProduct($product, $params);
				if (!empty($related)) {
					$cart->addProductsByIds(explode(',', $related));
				}

				$cart->save();

				$this->_getSession()->setCartWasUpdated(true);

			
				Mage::dispatchEvent('checkout_cart_add_product_complete',
				array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse())
				);

				if (!$cart->getQuote()->getHasError()){
					$message = $this->__('%s was added to your shopping cart.', Mage::helper('core')->htmlEscape($product->getName()));
					
					if( !Mage::getSingleton( 'customer/session' )->isLoggedIn() )
					{   
						$this->loadLayout();
						$session = Mage::getSingleton( 'customer/session' );
						$session->setBeforeAuthUrl($this->_getRefererUrl());
						$response['_status'] = 'SUCCESS';
					    $login_oneclickbuy = $this->renderLogin();
					    Mage::register('referrer_url', $this->_getRefererUrl());
					    $response['message'] = $login_oneclickbuy;

					} else {
						$response['_status'] = 'SUCCESS';
						$this->loadLayout();
						
						$_customer = Mage::getSingleton( 'customer/session' )->getCustomer();
					 	$address_id = Mage::getSingleton('customer/session')->getCustomer()->getDefaultBilling();
					 	$address = Mage::getModel('customer/address')->load($address_id);
					 	$applyrule=$this->getQuote()->getAppliedRuleIds();
						$applyaction=Mage::getModel('salesrule/rule')->load($applyrule)->getSimpleAction();
    					$this->getQuote()->getShippingAddress()
						->setCountryId($address->getCountryId())
						->setPostcode($address->getPostcode())
						->setCollectShippingRates(true);
						$this->_getQuote()->save();

						$moip_oneclickbuy = $this->renderPayment();
					
						Mage::register('referrer_url', $this->_getRefererUrl());
						
			        	$response['message'] = $moip_oneclickbuy;
					}
					
				}
			} catch (Mage_Core_Exception $e) {
				$msg = "";
				if ($this->_getSession()->getUseNotice(true)) {
					$msg = $e->getMessage();
				} else {
					$messages = array_unique(explode("\n", $e->getMessage()));
					foreach ($messages as $message) {
						$msg .= $message.'<br/>';
					}
				}

				$response['_status'] = 'ERROR';
				$response['message'] = $msg;
			} catch (Exception $e) {
				$response['_status'] = 'ERROR';
				$response['message'] = $this->__('<h4 class="modal-title">Não foi possível adcionar o produto via oneclickbuy.</h4>');
				Mage::logException($e);
			}
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
			return;
		}else{
			return parent::addAction();
		}
	}

	public function MoipOneclickbuyAction() {
		$datapost = $this->getRequest()->getPost();
		$address_billing = null; 
		$_customer = Mage::getSingleton( 'customer/session' )->getCustomer();
	 	$quote = $this->getQuote(); 
	 	$address_billing = $this->getQuote()->getBillingAddress();

	 	$customerAddressbilling = $this->getQuote()->getBillingAddress();
		
		#$address_billing->importCustomerAddress($customerAddressbilling);
		
		$storeId = Mage::app()->getStore()->getId();
 
		$checkout = Mage::getSingleton('checkout/type_onepage');
		 
		$checkout->initCheckout();
		 
		$checkout->saveCheckoutMethod('register');
	
		$checkout->saveShippingMethod($datapost["shipping_method"]);
		$additionaldata = array(
		 	'method' => 'moip_cc',
           	'moip_cc_count_cofre' => $datapost['moip_cc_count_cofre'],
            'moip_cc_payment_in_cofre' => '0',
            'moip_cc_use_cofre' => '1',
            'moip_cc_cofre_nb' => $datapost['moip_cc_cofre_nb'],
            'moip_cc_cofre_id' => $datapost['moip_cc_cofre_id'],
        );
    
		$this->getQuote()->getPayment()->importData($additionaldata);
		$checkout->saveOrder();
		try {
			
	
		    $allQuoteItems = $this->getQuote()->getAllItems();
		    foreach ($allQuoteItems as $_item) {
		        $_product = $_item->getProduct();
			    if ($_product->getIsPreparedToDelete()) {
			        $quote->removeItem($_item->getId());
			    }
		    }
		   	$this->getQuote()->save();
			$mensage['_status'] = "SUCCESS";
			$mensage['url_redirect'] = $quote->getPayment()->getOrderPlaceRedirectUrl();
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($mensage));
			return;
		}
		catch (Exception $ex) {
			$mensage['_status'] = "ERROR";
			$mensage['mensage'] = $ex->getMessage();
			return;
		}
	}

	public function LoginOneclickbuyAction() {
		$datapost = $this->getRequest()->getPost();
		
		$email = $datapost['email'];
		$password = $datapost['password'];

		$session = Mage::getSingleton('customer/session');

        try {
            $session->login($email, $password);
            $customer = $session->getCustomer();
            
            $session->setCustomerAsLoggedIn($customer);
            $_customer = Mage::getSingleton( 'customer/session' )->getCustomer();
		 	$address_id = Mage::getSingleton('customer/session')->getCustomer()->getDefaultBilling();
		 	$address = Mage::getModel('customer/address')->load($address_id);
		 	$applyrule=$this->getQuote()->getAppliedRuleIds();
			$applyaction=Mage::getModel('salesrule/rule')->load($applyrule)->getSimpleAction();
			$this->getQuote()->getShippingAddress()
			->setCountryId($address->getCountryId())
			->setPostcode($address->getPostcode())
			->setCollectShippingRates(true);
			$this->_getQuote()->save();
            $response['_status'] = 'SUCCESS';
			$response['mensage'] = $this->renderPayment();

        } catch(Exception $ex) {
           	$response['_status'] = 'ERROR';
			$response['mensage'] =  '<ul class="messages"><li class="error-msg"><ul><li><span>Login não pode ser feito, verifique seu e-mail e senha.</span></li></ul></li></ul>';
        }
		

		return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));

	}


}
