<?php
class MOIP_Onestepcheckout_Model_Sales_Quote_Address extends Mage_Sales_Model_Quote_Address //Mage_Customer_Model_Address_Abstract
{
   public function validate()
    {
		$errors = array();
		$helper = Mage::helper('customer');
		$this->implodeStreetAddress();
		if (!Zend_Validate::is($this->getFirstname(), 'NotEmpty')) {
			$errors[] = $helper->__('Please enter the first name.');
		}

		if (!Zend_Validate::is($this->getLastname(), 'NotEmpty')) {
			$errors[] = $helper->__('Please enter the last name.');
		}
		
		if(Mage::getStoreConfig('onestepcheckout/addfield/city') ==2){
			if (!Zend_Validate::is($this->getCity(), 'NotEmpty')) {
				$errors[] = $helper->__('Please enter the city.');
			}
		}
		if(Mage::getStoreConfig('onestepcheckout/addfield/telephone') ==2){
			if (!Zend_Validate::is($this->getTelephone(), 'NotEmpty')) {
				$errors[] = $helper->__('Please enter the telephone number.');
			}
		}
		if(Mage::getStoreConfig('onestepcheckout/addfield/zip') ==2){
			$_havingOptionalZip = Mage::helper('directory')->getCountriesWithOptionalZip();
			if (!in_array($this->getCountryId(), $_havingOptionalZip) && !Zend_Validate::is($this->getPostcode(), 'NotEmpty')) {
				$errors[] = $helper->__('Please enter the zip/postal code.');
			}
		}
		if(Mage::getStoreConfig('onestepcheckout/addfield/country')==2){
			if (!Zend_Validate::is($this->getCountryId(), 'NotEmpty')) {
				$errors[] = $helper->__('Please enter the country.');
			}
		}
		if(Mage::getStoreConfig('onestepcheckout/addfield/state')==2){
			if ($this->getCountryModel()->getRegionCollection()->getSize()
				   && !Zend_Validate::is($this->getRegionId(), 'NotEmpty')) {
				$errors[] = $helper->__('Please enter the state/province.');
			}
		}
		if (empty($errors) || $this->getShouldIgnoreValidation()) {
			return true;
		}
	
        return $errors;
    }	
}
