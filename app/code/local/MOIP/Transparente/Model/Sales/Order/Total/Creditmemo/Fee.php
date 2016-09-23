<?php

class MOIP_Transparente_Model_Sales_Order_Total_Creditmemo_Fee extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{

    protected $_code = 'fee';

    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();

        if($order->getFeeAmountInvoiced() > 0) {

            $feeAmountLeft = $order->getFeeAmountInvoiced() - $order->getFeeAmountRefunded();
            $basefeeAmountLeft = $order->getBaseFeeAmountInvoiced() - $order->getBaseFeeAmountRefunded();

            if ($basefeeAmountLeft > 0) {
                $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $feeAmountLeft);
                $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $basefeeAmountLeft);
                $creditmemo->setFeeAmount($feeAmountLeft);
                $creditmemo->setBaseFeeAmount($basefeeAmountLeft);
            }

        } else {

            $feeAmount = $order->getFeeAmountInvoiced();
            $basefeeAmount = $order->getBaseFeeAmountInvoiced();

            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $feeAmount);
            $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $basefeeAmount);
            $creditmemo->setFeeAmount($feeAmount);
            $creditmemo->setBaseFeeAmount($basefeeAmount);

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