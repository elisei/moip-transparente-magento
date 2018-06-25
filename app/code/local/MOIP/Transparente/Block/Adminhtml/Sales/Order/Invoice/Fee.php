<?php 
class MOIP_Transparente_Block_Adminhtml_Sales_Order_Invoice_Fee extends Mage_Adminhtml_Block_Sales_Totals
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
                        'base_value' => $baseAmt,
                        'label' => $this->helper('transparente')->__('Juros de parcelamento'),
                    )), 'fee_moip');
            return $this;
        } 
    }
}