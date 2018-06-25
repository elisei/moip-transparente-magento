<?php
class MOIP_Transparente_Block_Standard_Method_Cc extends Mage_Checkout_Block_Onepage_Success
{

	public function getMoipPayment()
    {
        return $this->getCheckout()->getMoipData();
    }
    public function getCardStatus()
    {
        $data             = $this->getMoipData();
        return $data['moip_init_status'];
    }

    public function getDescriptionStatusCancel()
    {
        $data             = $this->getMoipData();
        return $data['moip_init_status_descrip'];
    }

    public function getOrder()
    {
        $final = "";
        $orderId       = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $current_order = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('increment_id', $orderId);
        if ($current_order) {
            foreach ($current_order as $order) {
                $final = $order;
                break;
            }
        }
        return $final;
    }

  
    protected function getMoipData(){
        $additional = $this->getOrder()->getPayment()->getAdditionalData();
        return unserialize($additional);
    }

    public function getLinkReorder()
    {
        $order            = $this->getOrder();
        $orderid      = $order->getId();
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return $this->getUrl('sales/guest/reorder', array('order_id' => $order->getId()));
        }
        return $this->getUrl('sales/order/reorder', array('order_id' => $order->getId()));
    }

    public function getLinkViewOrder()
    {
        $order            = $this->getOrder();
        $orderid      = $order->getId();
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return $this->getUrl('sales/guest/view', array('order_id' => $order->getId()));
        }
        return $this->getUrl('sales/order/view', array('order_id' => $order->getId()));
    }
   
}