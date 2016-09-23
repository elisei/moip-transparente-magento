<?php

class  MOIP_Transparente_Block_Checkout_Totals_Fee extends Mage_Checkout_Block_Total_Default
{
	protected $_code = 'fee';

    protected function _initTotals() {
        parent::_initTotals();
        $shipping = $this->getSource()->getShippingAmount();
        $amt = $this->getSource()->getSubtotal() + $shipping;
        $total = $this->getSource()->getGrandTotal();
        $juros = $total - $amt;
        $amt = $this->getSource()->getFeeAmount();
        $baseAmt = $this->getSource()->getBaseFeeAmount();
        if ($juros != 0 ) {
            $address->addTotal(array(
                        'code' => 'fee',
                        'value' => $juros,
                        'title' => "Juros do parcelamento"
            ));
        } 
        return $this;
    }
    protected function _initTotals() {
        parent::_initTotals();
        $shipping = $this->getSource()->getShippingAmount();
        $amt = $this->getSource()->getSubtotal() + $shipping;
        $total = $this->getSource()->getGrandTotal();
        $juros = $total - $amt;
        $amt = $this->getSource()->getFeeAmount();
        $baseAmt = $this->getSource()->getBaseFeeAmount();
        if ($juros > 0) {
            $this->addTotal(new Varien_Object(array(
                        'code' => 'fee',
                        'value' => $juros,
                        'base_value' => $juros,
                        'label' => 'Juros de parcelamento',
                    )), 'fee');
            return $this;
        } 
    }
}