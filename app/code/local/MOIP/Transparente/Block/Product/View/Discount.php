<?php
class MOIP_Transparente_Block_Product_View_Discount extends Mage_Catalog_Block_Product_View {
	public function __construct(){
		parent::__construct();
	}

	public function getPercentual(){
		$rules = Mage::getResourceModel('salesrule/rule_collection')->load();

		foreach ($rules as $rule) {
			    if ($rule->getIsActive()) { 
			    	$conditions = "";
			    	$conditions_serialized = "";
			        $conditions_serialized = $rule->getConditionsSerialized();
			        $conditions = unserialize($conditions_serialized);
			        
			        
			        foreach ($conditions["conditions"] as $key => $value) {
			         

			            $_enable = ($value["attribute"] == 'payment_method' && ($value["value"] == 'moip_boleto' || $value["value"] == 'moip_tef') ) ? 1 : 0;
			            
			            if($_enable){
			                 $rule = Mage::getModel('salesrule/rule')->load($rule->getId()); 
			                 $percentual =  $rule->getDiscountAmount();
			            } 
			            
			        }		    
			}
			return $percentual; 
		}
	}

	public function getNewPriceDiscount(){
		$_product = $this->getProduct();
		$price = $_product->getFinalPrice();
		if($_product->getFinalPrice()) {
			$percentual = $this->getPercentual() / 100; 
	  		$valor_final = $price - ($percentual * $price);
			return Mage::helper('core')->currency($valor_final, true, false); 	
		} else {
			return;
		}
		
	}
}