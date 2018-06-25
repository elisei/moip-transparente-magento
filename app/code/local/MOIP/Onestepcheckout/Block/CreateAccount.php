<?php
class MOIP_Onestepcheckout_Block_CreateAccount extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {

		return parent::_prepareLayout();
    }
    
    

    public function getSession() {
        return Mage::getSingleton('checkout/session');
    }

    public function getTypeLayoutForm(){
       
        if(!$this->getSession()->getStep()){
           $output = $this->getLayout()->createBlock('core/template')->setBlockId('onestepcheckout_identify')->setTemplate('MOIP/onestepcheckout/cadastro/identify.phtml');
        } elseif($this->getSession()->getStep() == "login"){
            $output = $this->getLayout()->createBlock('checkout/onepage_login')->setBlockId('onestepcheckout_checkout_login')->setTemplate('MOIP/onestepcheckout/cadastro/logged/login-pre.phtml');
        } elseif ($this->getSession()->getStep() == "signup") {
            $output = $this->getLayout()->createBlock('onestepcheckout/checkout_address_edit')->setBlockId('checkout_onepage_address_edit');
            $agreements = $this->getLayout()->createBlock('checkout/agreements','agreements')->setTemplate('MOIP/onestepcheckout/checkout/daskboard/onepage/agreements.phtml');
            $output->setChild('agreements',$agreements);
            $output->setTemplate('MOIP/onestepcheckout/address/cadastro/form.phtml');
        }
        return $output->toHtml();
    }
}
