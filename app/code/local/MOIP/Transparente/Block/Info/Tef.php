<?php
class MOIP_Transparente_Block_Info_Tef extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('MOIP/transparente/info/tef.phtml');
    }
    protected function _prepareInfo()
    {
                $order = $this->getInfo()->getOrder();

                $customer_order = Mage::getModel('customer/customer')->load($order->getCustomerId());
                $order =  $order->getId();

                $model = Mage::getModel('transparente/write');
                $result = $model->load($order, 'mage_pay')->getData();
                
            return $result;
    }
}
