<?php
class MOIP_Onestepcheckout_Block_Checkout_Onepage_Billing_Sortbilling extends MOIP_Onestepcheckout_Block_Checkout_Onepage_Billing
{
	protected $type_address=array('company','email','street','city','state','zip','country','telephone','fax');
	public function isrequired($name_address){
		$status=Mage::getStoreConfig('onestepcheckout/addfield/'.$name_address);
		//$status=Mage::getStoreConfig('onestepcheckout/addfield/city');
		//echo $status;die();
		if($status=='2')
			return "required-entry";
		else
			return "";
	}
	public function isstar($name_address){
		$status=Mage::getStoreConfig('onestepcheckout/addfield/'.$name_address);
		if($status=='2')
			return "*";
		else
			return "";		
	}
	public function isdisable($name){
		$status=Mage::getStoreConfig('onestepcheckout/addfield/'.$name);
		if($status=='0')
			return true;
		else
			return false;				
	}
}
