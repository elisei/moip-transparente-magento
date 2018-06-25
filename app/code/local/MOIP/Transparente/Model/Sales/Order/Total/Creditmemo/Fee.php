<?php

class MOIP_Transparente_Model_Sales_Order_Total_Creditmemo_Fee extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{

    public function getLabel()
    {
        return Mage::helper('transparente')->__('Juros de parcelamento');
    }

    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {

        $order = $creditmemo->getOrder();

        $amount = $order->getFeeMoip();
        $amount_base = $order->getBaseFeeMoip();
        if ($amount) {
            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $amount);
            $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $amount_base);
        }

        return $this;
    }

}