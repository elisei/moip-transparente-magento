<?php
class MOIP_Transparente_Block_Product_View extends Mage_Catalog_Block_Product_View{
	public function __construct(){
		parent::__construct();
	}
	public function getParcelamento($price, $method) {
		$api = Mage::getModel('transparente/api');
		if($price){
			$parcelamento = $api->getParcelamento($price);
			$parcela_decode = json_decode($parcelamento,true);
			foreach ($parcela_decode as $key => $value):
			    if ($key <= Mage::getSingleton('transparente/standard')->getConfigData('nummaxparcelamax')):
			        $juros = $parcela_decode[$key]['juros'];
			        $parcelas_result = $parcela_decode[$key]['parcela'];
			        $total_parcelado = $parcela_decode[$key]['total_parcelado'];
			        if($juros > 0)
			            $asterisco = '';
			        else
			            $asterisco = ' sem juros';
			        $parcelas[]= $key.'x de '.$parcelas_result.$asterisco;
			    endif;
			endforeach;
			if($method == 'reduzido'){
				return end($parcelas);
			} elseif($method == 'integral') {
				return $parcelas;
			} else {
				return ;
			}

		} else {
			return ;
		}


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

}