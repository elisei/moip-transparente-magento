<?php
class MOIP_Onestepcheckout_Model_Customer_Customer extends Mage_Customer_Model_Customer 
{
    public function validate()
    {
        $errors = array();
        $customerHelper = Mage::helper('customer');
	
			if (!Zend_Validate::is( trim($this->getFirstname()) , 'NotEmpty')) {
				$errors[] = $customerHelper->__('The first name cannot be empty.');
			}
			if (!Zend_Validate::is( trim($this->getLastname()) , 'NotEmpty')) {
				$errors[] = $customerHelper->__('The last name cannot be empty.');
			}
		
			$password = $this->getPassword();
			if (!$this->getId() && !Zend_Validate::is($password , 'NotEmpty')) {
				$errors[] = $customerHelper->__('The password cannot be empty.');
			}
			if (strlen($password) && !Zend_Validate::is($password, 'StringLength', array(6))) {
				$errors[] = $customerHelper->__('The minimum password length is %s', 6);
			}
			$confirmation = $this->getConfirmation();
			if ($password != $confirmation) {
				$errors[] = $customerHelper->__('Please make sure your passwords match.');
			}

			

			if (empty($errors)) {
				return true;
			}
			return $errors;
		
    	
    }
}
