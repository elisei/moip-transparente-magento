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