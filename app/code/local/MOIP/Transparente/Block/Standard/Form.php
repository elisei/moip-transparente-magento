<?php
/**
 * Transparente - Transparente Payment Module
 *
 * @title      Magento -> Custom Payment Module for Transparente (Brazil)
 * @category   Payment Gateway
 * @package    MOIP_Transparente
 * @author     Transparente Pagamentos S/a
 * @copyright  Copyright (c) 2010 Transparente Pagamentos S/A
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class MOIP_Transparente_Block_Standard_Form extends Mage_Payment_Block_Form {

	protected function _construct() {
		$this->setTemplate('MOIP/transparente/horizontal_form.phtml');
		parent::_construct();
	}


	protected function _prepareLayout() {
		$this->setChild('scripts', $this->getLayout()->createBlock('core/template') ->setTemplate('MOIP/transparente/script.phtml'));
		return parent::_prepareLayout();
	}

	public function getPublicKey(){
	if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
                   return Mage::getSingleton('transparente/standard')->getConfigData('publickey_dev');
                } else {
                       return Mage::getSingleton('transparente/standard')->getConfigData('publickey_prod');
                }
	}

	public function mostraBoleto() {
		if (strpos(Mage::getSingleton('transparente/standard')->getConfigData('formas_pagamento'), "BoletoBancario") !== false) {
			return true;
		}else {
			return false;
		}
	}
	public function mostraTransferencia() {
		if (strpos(Mage::getSingleton('transparente/standard')->getConfigData('formas_pagamento'), "DebitoBancario") !== false) {
			return true;
		}else {
			return false;
		}
	}
	public function mostraCartao() {
		if (strpos(Mage::getSingleton('transparente/standard')->getConfigData('formas_pagamento'), "CartaoCredito") !== false) {
			return true;
		}else {
			return false;
		}
	}



	public function getBoletoIcon() {
		if (Mage::getStoreConfig('moipall/config/trocar_icone')) {
			return Mage::getBaseUrl('media') . "moip/alltransparente/". Mage::getStoreConfig('moipall/config/icone_boleto');
		}else {
			return $this->getSkinUrl('MOIP/transparente/imagem/ababoleto.png');
		}
	}
	public function getTransferenciaIcon() {
		if (Mage::getStoreConfig('moipall/config/trocar_icone')) {
			return Mage::getBaseUrl('media') . "moip/alltransparente/". Mage::getStoreConfig('moipall/config/icone_transf');
		}else {
			return $this->getSkinUrl('MOIP/transparente/imagem/abatransf.png');
		}
	}
	public function getCartaoIcon() {
		if (Mage::getStoreConfig('moipall/config/trocar_icone')) {
			return Mage::getBaseUrl('media') . "moip/alltransparente/". Mage::getStoreConfig('moipall/config/icone_cartao');
		}else {
			return $this->getSkinUrl('MOIP/transparente/imagem/abacartao.png');
		}
	}

	//imagem do boleto
	public function getBoletoImage() {
		if (Mage::getStoreConfig('moipall/config/trocar_bandeira_cartao')) {
			return Mage::getBaseUrl('media') . "moip/alltransparente/". Mage::getStoreConfig('moipall/config/boleto');
		}else {
			return $this->getSkinUrl('MOIP/transparente/imagem/Bradesco.png');
		}
	}

	//imagens de transferencia
	public function getBBImage() {
		if (Mage::getStoreConfig('moipall/config/trocar_bandeira_cartao')) {
			return Mage::getBaseUrl('media') . "moip/alltransparente/". Mage::getStoreConfig('moipall/config/cartao_bb');
		}else {
			return $this->getSkinUrl('MOIP/transparente/imagem/BancoDoBrasil.png');
		}
	}
	public function getBradescoImage() {
		if (Mage::getStoreConfig('moipall/config/trocar_bandeira_cartao')) {
			return Mage::getBaseUrl('media') . "moip/alltransparente/". Mage::getStoreConfig('moipall/config/cartao_bradesco');
		}else {
			return $this->getSkinUrl('MOIP/transparente/imagem/Bradesco.png');
		}
	}
	public function getItauImage() {
		if (Mage::getStoreConfig('moipall/config/trocar_bandeira_cartao')) {
			return Mage::getBaseUrl('media') . "moip/alltransparente/". Mage::getStoreConfig('moipall/config/cartao_itau');
		}else {
			return $this->getSkinUrl('MOIP/transparente/imagem/Itau.png');
		}
	}
	public function getBanrisulImage() {
		if (Mage::getStoreConfig('moipall/config/trocar_bandeira_cartao')) {
			return Mage::getBaseUrl('media') . "moip/alltransparente/". Mage::getStoreConfig('moipall/config/cartao_banrisul');
		}else {
			return $this->getSkinUrl('MOIP/transparente/imagem/Banrisul.png');
		}
	}



	//confs de parcelamento
	public function getParcelamento($dataToReturn) {
		$parcelas = array();
		$k = "";
		$parcelax = "";
		$precox = "";
		$api = Mage::getSingleton('transparente/api');


		$cartTotal = Mage::getModel('checkout/session')->getQuote()->getGrandTotal();

		if ($cartTotal > 5) {
			$parcelamento = $api->getParcelamento($cartTotal);
			$parcela_decode = Mage::helper('core')->jsonDecode($parcelamento,true);
			foreach ($parcela_decode as $key => $value) {

				if ($key <= Mage::getSingleton('transparente/standard')->getConfigData('nummaxparcelamax')) {
							$juros = $parcela_decode[$key]['juros'];
							$parcelas_result = $parcela_decode[$key]['parcela'];
							$total_parcelado = $parcela_decode[$key]['total_parcelado'];
							if($juros > 0)
								$asterisco = '*';
							else
								$asterisco = '';
							$parcelas[]= '<option value="'.$key.'">'.$key.'x de '.$parcelas_result.' no total de '.$total_parcelado.' '.$asterisco.'</option>';
						#	$parcelas[]= '<li><input type="radio" name="payment[credito_parcelamento]" title="Selecione as Parcelas" id="credito_parcelamento" class="input-radio  validate-one-required-by-name" value="'.$key.'"><label>'.$key.'x de '.$parcelas_result.' no total de '.$total_parcelado.' '.$asterisco.'</label></li>';
					}
				}
		}else {
			$parcelas = "<option value=\"1\"> Pagamento à vista </option>";
		}

		if ($dataToReturn == 'preview') {
			if($key > 1){
				return "{$parcelas_result}";
			}
			else {
				return "{$total_parcelado}";
			}
		}
		if ($dataToReturn == 'preview_parcelas') {
			if($key > 1){
			return "Pague em até {$key}x";
			} else {
				return "Pague em à vista";
			}

		}
		if ($dataToReturn == 'parcelas'){
			return $parcelas;
		}
	}
	public function imageCofre($brand){
		if($brand == "Visa"){
			$image_brand = $this->getVisaImage();
		} elseif ($brand == "Mastercard") {
			$image_brand = $this->getMastercardImage();
		} elseif ($brand == "AmericanExpress") {
			$image_brand = $this->getAmericanExpressImage();
		} elseif ($brand == "Diners") {
			$image_brand = $this->getDinersImage();
		} elseif ($brand == "Hipercard") {
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
			$table = (string) Mage::getConfig()->getTablePrefix().'moip_transparente';

			$query = 'SELECT * FROM ' . $table .' WHERE customer_id='.$customerData->getID().' AND cofre IS NOT NULL';
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

	public function getDateCard($select){
		if($this->getQuote()->getBillingAddress()){
			$checkout = $this->getQuote()->getBillingAddress();
			if($select == "name"){
				return  $checkout->getFirstname()." ".$checkout->getLastname();
			} elseif($select == "telephone-ddd") {
				return  $this->getNumberOrDDD($checkout->getTelephone(), true);
			} elseif($select == "telephone-number") {
				return  $this->getNumberOrDDD($checkout->getTelephone(), false);
			}   elseif($select =="taxvat"){
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

	public function getNumberOrDDD($param_telefone, $param_ddd = false) {

            $cust_ddd = '11';
            $cust_telephone = preg_replace("/[^0-9]/", "", $param_telefone);
            $st = strlen($cust_telephone) - 8;
            if ($st > 0) {
                $cust_ddd = substr($cust_telephone, 0, 2);
                $cust_telephone = substr($cust_telephone, $st, 8);
            }

            if ($param_ddd === false) {
                $retorno = $cust_telephone;
            } else {
                $retorno = $cust_ddd;
            }

            return $retorno;
        }
	public function getCheckout() {
		return Mage::getSingleton('checkout/session');
	}


	public function getQuote() {
		return $this->getCheckout()->getQuote();
	}


	public function getOnepage() {
		return (string)Mage::getSingleton('checkout/type_onepage');
	}


}
