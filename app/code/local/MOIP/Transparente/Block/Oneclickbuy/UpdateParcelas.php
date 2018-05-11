<?php 
class MOIP_Transparente_Block_Oneclickbuy_UpdateParcelas extends Mage_Core_Block_Template
{

	public function getInstallmentSelect() {
		$ammout = $this->getQuote()->getGrandTotal();
		if($ammout){
			$installment =  Mage::helper('transparente')->getCalcInstallment($ammout);
		

			$installments = array();
         
            foreach ($installment as $key => $_installment):      
                $_interest = $_installment['interest'];
                
                if($_interest > 0)
                    $text_interest = $this->__(' no valor total %s', $_installment['total_installment']);
                else
                    $text_interest = $this->__(' sem juros');
                if($key >=2){
                    $installments[]= $this->__('<option value="%s">%sx de %s%s</option>',$key,$key,$_installment['installment'],$text_interest);    
                } else {
                    $installments[]= $this->__('<option value="1">À vista no valor total %s</option>',Mage::helper('core')->currency($ammout, true, false));
                }
            endforeach;


		return $installments;
		}
	}

	public function getCheckout() {
		return Mage::getSingleton('checkout/session');
	}


	public function getQuote() {
		return $this->getCheckout()->getQuote();
	}
	
}