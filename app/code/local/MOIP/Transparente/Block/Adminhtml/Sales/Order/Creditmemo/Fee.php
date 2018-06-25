<?php
class MOIP_Transparente_Block_Adminhtml_Sales_Order_Creditmemo_Fee extends Mage_Adminhtml_Block_Sales_Order_Creditmemo_Totals
{
    /**
     * Initialize order totals array
     *
     * @return Mage_Sales_Block_Order_Totals
     */
    protected function _initTotals()
    {
        parent::_initTotals();
        $orderid = $this->getSource()->getOrderId();
        $order = Mage::getModel('sales/order')->load($orderid);
        $amount = FEE_MOIP;
        $shipping = $order->getShippingAmount();

        $amt = $order->getSubtotal() + $shipping;
       
        $total = $order->getGrandTotal();
        
        $juros = $total - $amt;
        if($juros) {
            $this->addTotalBefore(new Varien_Object(array(
                        'code' => 'fee_moip',
                        'value' => $juros,
                        'base_value' => $juros,
                        'label' => $this->helper('transparente')->__('Juros de parcelamento'),
                    )), 'fee_moip');
        }
        
    }

}