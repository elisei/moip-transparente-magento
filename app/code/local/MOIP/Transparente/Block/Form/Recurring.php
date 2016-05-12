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

class MOIP_Transparente_Block_Form_Recurring extends Mage_Payment_Block_Form {
	protected function _construct() {
		parent::_construct();

		return $this->setTemplate('MOIP/transparente/form/recurring.phtml');
	}
	public function getPublicKey(){
	if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
                   return Mage::getSingleton('transparente/standard')->getConfigData('publickey_dev');
                } else {
                       return Mage::getSingleton('transparente/standard')->getConfigData('publickey_prod');
                }
	}

	
	public function mostraCartao() {
		if (strpos(Mage::getSingleton('transparente/standard')->getConfigData('formas_pagamento'), "CartaoCredito") !== false) {
			return true;
		}else {
			return false;
		}
	}



	public function getCartaoIcon() {
		if (Mage::getStoreConfig('moipall/config/trocar_icone')) {
			return Mage::getBaseUrl('media') . "moip/alltransparente/". Mage::getStoreConfig('moipall/config/icone_cartao');
		}else {
			return $this->getSkinUrl('MOIP/transparente/imagem/abacartao.png');
		}
	}

	//confs de parcelamento
	public function getParcelamentoSelect() {
		
		$api = Mage::getSingleton('transparente/api');
		$parcelamento = $api->getParcelamento();

			
			foreach ($parcelamento as $key => $value) {

						if($key > 0){
							$juros = $value['juros'];
							$parcelas_result = $value['parcela'];
							$total_parcelado = $value['total_parcelado'];
							
							if($juros > 0)
								$asterisco = '*';
							else
								$asterisco = '';
							
								$parcelas[]= '<option value="'.$key.'">'.$key.'x de '.$parcelas_result.' no total de '.$total_parcelado.$asterisco.'</option>';
								#$parcelas[]= '<li><input type="radio" name="payment[credito_parcelamento]" title="Selecione as Parcelas" id="credito_parcelamento" class="input-radio  validate-one-required-by-name" value="'.$key.'"><label>'.$key.'x de '.$parcelas_result.' no total de '.$total_parcelado.' '.$asterisco.'</label></li>';
							
						}
			}
			

	return $parcelas;

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
	
	public function getHiperImage() {
		if (Mage::getStoreConfig('moipall/config/trocar_bandeira_cartao')) {
			return Mage::getBaseUrl('media') . "moip/alltransparente/". Mage::getStoreConfig('moipall/config/cartao_hiper');
		}else {
			return $this->getSkinUrl('MOIP/transparente/imagem/Hiper.png');
		}
	}

	public function getEloImage() {
		if (Mage::getStoreConfig('moipall/config/trocar_bandeira_cartao')) {
			return Mage::getBaseUrl('media') . "moip/alltransparente/". Mage::getStoreConfig('moipall/config/cartao_elo');
		}else {
			return $this->getSkinUrl('MOIP/transparente/imagem/Elo.png');
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
		} elseif ($brand == "HIPPERCARD") {
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