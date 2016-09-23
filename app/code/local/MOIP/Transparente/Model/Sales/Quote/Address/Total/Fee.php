<?php
class MOIP_Transparente_Model_Sales_Quote_Address_Total_Fee extends Mage_Sales_Model_Quote_Address_Total_Abstract{
    protected $_code = 'fee';


    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $amt = $address->getFeeAmount();
        if($amt != 0){
            $address->addTotal(array(
                    'code'=>$this->getCode(),
                    'title'=>'Juros de parcelamento',
                    'value'=> $amt
            ));
          
        } 
        return $this;
    }
}