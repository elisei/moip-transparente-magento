<?php
class MOIP_Transparente_Block_Product_View_Parcelas extends Mage_Catalog_Block_Product_View {


	public function __construct(){
		parent::__construct();
	}

   
    public function getListInstallments($method) {
        $product            = $this->getProduct();
        $sales              = $product->isSaleable();
        $IsRecurring        = $product->getIsRecurring();
        $price              = $product->getFinalPrice();
        if($sales && !$IsRecurring && $price){
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

    public function getVisaImage() {
        return $this->getSkinUrl('MOIP/transparente/imagem/Visa.png');
    }
    public function getMastercardImage() {
        return $this->getSkinUrl('MOIP/transparente/imagem/Mastercard.png');
    }
    public function getDinersImage() {
        return $this->getSkinUrl('MOIP/transparente/imagem/Diners.png');
    }
    public function getAmericanExpressImage() {
        return $this->getSkinUrl('MOIP/transparente/imagem/AmericanExpress.png');
    }
    public function getHipercardImage() {
        return $this->getSkinUrl('MOIP/transparente/imagem/Hipercard.png');
    }
    public function getHiperImage() {
        return $this->getSkinUrl('MOIP/transparente/imagem/Hiper.png');
    }
    public function getEloImage() {
        return $this->getSkinUrl('MOIP/transparente/imagem/Elo.png');
    }
}