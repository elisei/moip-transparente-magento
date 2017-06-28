<?php

class MOIP_Transparente_RecurringViewController extends Mage_Core_Controller_Front_Action 
{

	public function preDispatch()
    {	
        parent::preDispatch();
        if (!$this->getRequest()->isDispatched()) {
            return;
        }
        $this->_session = Mage::getSingleton('customer/session');
        if (!$this->_session->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
        }
        Mage::register('current_customer', $this->_session->getCustomer());
    }
   
    public function getApi()
    {
        $api = Mage::getModel('transparente/recurringapi');
        return $api;
    }


    public function paymentupdateAction()
    {	

   		$this-> _initProfile();
   		if (!$this->_validateFormKey()) {
  				$this->_redirectReferer();
 			 return;
 		}
   		$profile 			= Mage::getModel('sales/recurring_profile')->load(
				   																$this->getRequest()->getParam('profile')
				   															);
    	$data 				= $this->getRequest()->getPost();
    	$api 				= $this->getApi();
   		$update_response = $api->updateBillingCard($data, $profile->getCustomerId());
   		$decode_response = json_decode($update_response, true);
   		if(isset($decode_response['errors'])){
   			foreach ($decode_response['errors'] as $key => $value) {
   				Mage::getSingleton('core/session')->addError($value['description']);

   			}
   		} else {
   			Mage::getSingleton('core/session')->addSuccess("Cartão atualizado com sucesso, quando ocorrer a nova cobrança seu dado será atualizado em nossos registros.");
   		}
   		$this->_redirectReferer();
   		return true;
    }

    protected function _initProfile()
    {
        
        $profile = Mage::getModel('sales/recurring_profile')->load($this->getRequest()->getParam('profile'));
        if (!$profile->getId() || $this->_session->getCustomerId() != $profile->getCustomerId()) {
            Mage::throwException($this->__('Specified profile does not exist.'));
        }
        Mage::register('current_recurring_profile', $profile);
        return $profile;
    }
   
	
}