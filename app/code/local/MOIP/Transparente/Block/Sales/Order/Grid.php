<?php
class MOIP_Transparente_Block_Sales_Order_Grid extends Mage_Adminhtml_Block_Sales_Order_Grid
{   
    protected function _prepareMassaction()
    {
        parent::_prepareMassaction();
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/setstate')) {
            $this->getMassactionBlock()->addItem(
                'transparente',
                array('label' => $this->__('Consultar status no Moip'), 
                      'url'   =>  Mage::helper('adminhtml')->getUrl('adminhtml/adminhtml_statusmoip/setstate'),
                )
            );
        }
    }
}