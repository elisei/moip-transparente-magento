<?php

class MOIP_Transparente_Model_Sales_Order_Total_Invoice_Fee extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{


    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {

        $order = $invoice->getOrder();

        $amount = $order->getFeeMoip();
        $amount_base = $order->getBaseFeeMoip();
        if ($amount) {
            $invoice->setGrandTotal($invoice->getGrandTotal() + $amount);
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $amount_base);
        }

        return $this;
    }

   
}