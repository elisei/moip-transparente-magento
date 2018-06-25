<?php
class MOIP_Transparente_Model_Sales_Quote_Address_Total_Fee extends Mage_Sales_Model_Quote_Address_Total_Abstract{
   

    public function __construct()
    {
        $this->setCode('fee_moip');
    }
    public function getLabel()
    {
        return Mage::helper('transparente')->__('Juros de parcelamento');
    }
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);
        $items = $this->_getAddressItems($address);
        if (!count($items)) {
            return $this;
        }


        $amount = 'fee_moip';

        if ($amount) {
            $this->_addAmount($amount);
            $this->_addBaseAmount($amount);
        }

        return $this;
    }
    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $amt = $address->getFeeMoip();
        $baseamt = $address->getBaseFeeMoip();
        if($amt != 0){
            $address->addTotal(array(
                    'code'=>$this->getCode(),
                    'title'=>$this->getLabel(),
                    'value'=> $amt
            ));
        } 
        return $this;
    }
}