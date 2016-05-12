<?php
class MOIP_Onestepcheckout_Block_Checkout_Cart_Promotion extends Mage_Checkout_Block_Cart_Abstract {
	public function getTotals()
   	{
      	$quote = Mage::getSingleton('checkout/session')->getQuote();
		$subtotal = $quote->getSubtotal();
         return $subtotal;
    }

    public function getDiffCart(){
    	$totals = $this->getTotals();
    	if($totals < 99){
    		
    		$diff = 100 - $totals;
    		return $diff;

    	} elseif($totals >= 100 && $totals < 200){
    		
    		$diff = 200 - $totals;
    		return $diff;

    	} elseif($totals >= 200){
    		
    		return false;

    	} else {
    		return false;
    	}

    }

    public function getConditionCart(){
    	$totals = $this->getTotals();
    	if($totals < 100){
    		return 1;
    	} elseif($totals >= 100 && $totals < 200){    		
    		return 2;
    	} elseif($totals >= 200){
    		return 3;
    	} else {
    		return false;
    	}

    }
}