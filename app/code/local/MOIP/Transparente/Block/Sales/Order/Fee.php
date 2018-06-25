<?php
class MOIP_Transparente_Block_Sales_Order_Fee extends Mage_Sales_Block_Order_Totals
{
    protected $_code = 'fee_moip';

    protected function _initTotals() {
        parent::_initTotals();
        
        $amt = $this->getSource()->getFeeMoip();
        $baseAmt = $this->getSource()->getBaseFeeMoip();

        if ($amt > 0) {

            $this->addTotal(new Varien_Object(array(
                        'code' => 'fee_moip',
                        'value' => $amt,
                        'label' => $this->helper('transparente')->__('Juros de parcelamento'),
                    )), 'tax');
            return $this;
        } 
    }

}

?>