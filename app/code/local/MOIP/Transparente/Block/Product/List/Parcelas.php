<?php
class MOIP_Transparente_Block_Product_List_Parcelas extends Mage_Core_Block_Template {
	public function __construct(){
		parent::__construct();
	}

	public function getParcelamento($price, $method) {
		
		if($price){
			$installment =  Mage::helper('transparente')->getCalcInstallment($price);
            $installments = array();
         
            foreach ($installment as $key => $_installment):      
                $_interest = $_installment['interest'];
                
                if($_interest > 0)
                    $text_interest = $this->__('*');
                else
                    $text_interest = $this->__(' sem juros');
                if($key >=2){
                    $installments[]= $this->__('em até <strong>%sx</strong> de %s%s',$key,$_installment['installment'],$text_interest);    
                } else {
                    $installments[]= $this->__('À vista no valor total <strong>%s</strong>',Mage::helper('core')->currency($price, true, false));
                }
            endforeach;

            if($method == 'reduzido'){
                $last_zero_interest = Mage::helper('transparente')->getFilterNoInterestRate($installment);
                $last_text_zero_interest = end(array_keys($last_zero_interest));
                return $installments[$last_text_zero_interest-1];
            } elseif($method == 'integral') {
                return $installments;
            } else {
                return $this;
            }
		} else {
			return $this;
		}


	}
	

}