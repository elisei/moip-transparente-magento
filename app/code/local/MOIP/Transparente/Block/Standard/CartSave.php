<?php
class MOIP_Transparente_Block_Standard_CartSave extends Mage_Checkout_Block_Onepage_Success{
	public function __construct(){
		parent::__construct();
	}

	public function getVisaImage() {
		if (Mage::getStoreConfig('moipall/config/trocar_bandeira_cartao')) {
			return Mage::getBaseUrl('media') . "moip/alltransparente/". Mage::getStoreConfig('moipall/config/cartao_visa');
		}else {
			return $this->getSkinUrl('MOIP/transparente/imagem/Visa.png');
		}
	}
	public function getMastercardImage() {
		if (Mage::getStoreConfig('moipall/config/trocar_bandeira_cartao')) {
			return Mage::getBaseUrl('media') . "moip/alltransparente/". Mage::getStoreConfig('moipall/config/cartao_master');
		}else {
			return $this->getSkinUrl('MOIP/transparente/imagem/Mastercard.png');
		}
	}
	public function getDinersImage() {
		if (Mage::getStoreConfig('moipall/config/trocar_bandeira_cartao')) {
			return Mage::getBaseUrl('media') . "moip/alltransparente/". Mage::getStoreConfig('moipall/config/cartao_diners');
		}else {
			return $this->getSkinUrl('MOIP/transparente/imagem/Diners.png');
		}
	}
	public function getAmericanExpressImage() {
		if (Mage::getStoreConfig('moipall/config/trocar_bandeira_cartao')) {
			return Mage::getBaseUrl('media') . "moip/alltransparente/". Mage::getStoreConfig('moipall/config/cartao_american');
		}else {
			return $this->getSkinUrl('MOIP/transparente/imagem/AmericanExpress.png');
		}
	}
	public function getHipercardImage() {
		if (Mage::getStoreConfig('moipall/config/trocar_bandeira_cartao')) {
			return Mage::getBaseUrl('media') . "moip/alltransparente/". Mage::getStoreConfig('moipall/config/cartao_hipercard');
		}else {
			return $this->getSkinUrl('MOIP/transparente/imagem/Hipercard.png');
		}
	}
	public function imageCofre($brand){
		if($brand == "VISA"){
			$image_brand = $this->getVisaImage();
		} elseif ($brand == "MASTERCARD") {
			$image_brand = $this->getMastercardImage();
		} elseif ($brand == "AMERICANEXPRESS") {
			$image_brand = $this->getAmericanExpressImage();
		} elseif ($brand == "DINERS") {
			$image_brand = $this->getDinersImage();
		} elseif ($brand == "HIPERCARD") {
			$image_brand = $this->getHipercardImage();
		} else {
			$image_brand = "";
		}
		return $image_brand;
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
				return 'false';
			}

		} else {
			return 'false';
		}

	}
}
