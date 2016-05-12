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
			$data_array = array();
			$customerData = Mage::getSingleton('customer/session')->getCustomer();
			$resource = Mage::getSingleton('core/resource');

			$readConnection = $resource->getConnection('core_read');
			$table = (string) Mage::getConfig()->getTablePrefix().'moip_transparentev2';

			$query = 'SELECT * FROM ' . $table .' WHERE customer_id='.$customerData->getID().' AND moip_card_id IS NOT NULL';
			$results = $readConnection->fetchAll($query);

			if($results){
				return Mage::helper('core')->jsonEncode((object)$results);
			} else {
				return 'false';
			}

		} else {
			return 'false';
		}

	}
}
