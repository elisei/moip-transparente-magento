<?php

class MOIP_Transparente_Block_Form_Cc extends Mage_Payment_Block_Form {

	protected function _construct() {

		$this->setTemplate('MOIP/transparente/form/cc.phtml');
		return parent::_construct();
	}

	protected function _prepareLayout(){
		if($this->getLayout()->getBlock('head')){
			$this->getLayout()->getBlock('head')->addJs('MOIP/core/jquery.js');
        	$this->getLayout()->getBlock('head')->addJs('MOIP/core/jquery_noconflict.js');
        	$this->getLayout()->getBlock('head')->addJs('MOIP/transparente/moip.js');
        	$block = $this->getLayout()->createBlock('core/template')->setTemplate('MOIP/transparente/form/external_js.phtml');
          	$this->getLayout()->getBlock('content')->append($block);
		}
        
        return parent::_prepareLayout();
    }
		
	
	public function getPublicKey(){
		if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
           return Mage::getSingleton('transparente/standard')->getConfigData('publickey_dev');
        } else {
            return Mage::getSingleton('transparente/standard')->getConfigData('publickey_prod');
        }
	}

	//confs de parcelamento
	public function getInstallmentSelect() {
		$ammout = $this->getQuote()->getGrandTotal();
		if($ammout){
			if(!Mage::getStoreConfig('payment/moip_cc/parcelas_avancadas')){
				$installment =  Mage::helper('transparente')->getCalcInstallment($ammout);
			} else {
				$installment =  Mage::helper('transparente')->getComplexCalcInstallment($ammout);	
			}
			
			

			$installments = array();
         
            foreach ($installment as $key => $_installment):      
                $_interest = $_installment['interest'];
                
                if($_interest > 0)
                    $text_interest = $this->__(' no valor total %s', $_installment['total_installment']);
                else
                    $text_interest = $this->__(' sem juros');
                if($key >=2){
                    $installments[]= $this->__('<option value="%s">%sx de %s%s</option>',$key,$key,$_installment['installment'],$text_interest);    
                } else {
                    $installments[]= $this->__('<option value="1">À vista no valor total %s</option>',Mage::helper('core')->currency($ammout, true, false));
                }
            endforeach;


		return $installments;
		}
	}
	public function getVisaImage() {
		
			return $this->getSkinUrl('MOIP/transparente/imagem/Visa.png');
		
	}
	public function getMastercardImage() {
		
			return $this->getSkinUrl('MOIP/transparente/imagem/Mastercard.png');
		
	}
	public function getDinersImage() {
		
			return $this->getSkinUrl('MOIP/transparente/imagem/Diners.png');
		
	}
	public function getAmericanExpressImage() {
		
			return $this->getSkinUrl('MOIP/transparente/imagem/AmericanExpress.png');
		
	}
	public function getHipercardImage() {
		
			return $this->getSkinUrl('MOIP/transparente/imagem/Hipercard.png');
		
	}
	
	public function getHiperImage() {
		
			return $this->getSkinUrl('MOIP/transparente/imagem/Hiper.png');
		
	}

	public function getEloImage() {
		
			return $this->getSkinUrl('MOIP/transparente/imagem/Elo.png');
		
	}

	public function imageCofre($brand){
		if($brand == "VISA"){
			$image_brand = $this->getVisaImage();
		} elseif ($brand == "MASTERCARD") {
			$image_brand = $this->getMastercardImage();
		} elseif ($brand == "AMEX") {
			$image_brand = $this->getAmericanExpressImage();
		} elseif ($brand == "DINERS") {
			$image_brand = $this->getDinersImage();
		} elseif ($brand == "HIPERCARD") {
			$image_brand = $this->getHipercardImage();
		} elseif ($brand == "HIPER") {
			$image_brand = $this->getHiperImage();
		} elseif ($brand == "ELO") {
			$image_brand = $this->getEloImage();		
		} else {
			$image_brand = "";
		}
		return $image_brand;
	}

	public function getCustomerState(){
		if(Mage::getSingleton('customer/session')->isLoggedIn()){
			$taxvat = $this->getQuote()->getCustomer()->getTaxvat();
			$taxvat        = preg_replace("/[^0-9]/", "", $taxvat);
		         if(strlen($taxvat) == 11)
		            return 1;
		        else 
		            return !1;			
		} else{
			return !1;	
		}
		
	}

	public function getCofre() {
		if (Mage::getSingleton('customer/session')->isLoggedIn()) {
			$customerData = Mage::getSingleton('customer/session')->getCustomer();
			$ambiente = Mage::getSingleton('transparente/standard')->getConfigData('ambiente');
			$model = Mage::getModel('transparente/transparente');
			$collection = $model->getCollection()
							->addFieldToSelect(array('moip_card_id','moip_card_brand','moip_card_first6','moip_card_last4','moip_card_fullname'))
							->addFieldToFilter('customer_id', array('eq' => $customerData->getId()))
							->addFieldToFilter('moip_ambiente', array('eq' => $ambiente))
							->addFieldToFilter('moip_card_id', array('neq' => 'NULL'));
			$collection->getSelect()->group('moip_card_id');
			if($collection->getSize() >= 1){
				return $collection;
			} else {
				return false;
			}

		} else {
			return false;
		}

	}

	public function getDateCard($select){
		if($this->getQuote()->getBillingAddress()){
			$checkout = $this->getQuote()->getBillingAddress();
			if($select == "name"){
				return  $checkout->getFirstname()." ".$checkout->getLastname();
			} elseif($select =="taxvat"){
				return $this->getQuote()->getCustomer()->getTaxvat();
			} elseif($select == "dob"){
				return $this->getQuote()->getCustomer()->getDob();
			} elseif($select == "dob-day"){
				return Mage::app()->getLocale()->date($this->getQuote()->getCustomer()->getDob(), null, null, false)->toString('dd');
			} elseif($select =="dob-month") {
				return Mage::app()->getLocale()->date($this->getQuote()->getCustomer()->getDob(), null, null, false)->toString('MM');
			} elseif ($select == "dob-year") {
				return Mage::app()->getLocale()->date($this->getQuote()->getCustomer()->getDob(), null, null, false)->toString('Y');
			}
			else{
				return;
			}
		}
		else {
			return;
		}
	}

	
	public function getCheckout() {
		if(!Mage::app()->getStore()->isAdmin()) {
			return Mage::getSingleton('checkout/session');
		} else {
			return Mage::getSingleton('adminhtml/session_quote');
		}
		
	}


	public function getQuote() {
		return $this->getCheckout()->getQuote();
	}


}
