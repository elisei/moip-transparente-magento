<?php
class MOIP_Onestepcheckout_Block_Adminhtml_Onestepcheckout_Sales_Order_Grid_Column extends Mage_Adminhtml_Block_Sales_Order_Grid
{
	 
    protected function _preparePage()
    {

        $attribute = Mage::getModel('eav/config')->getAttribute('customer', 'tipopessoa');
        $allOptions = $attribute->getSource()->getAllOptions(false);
        /*var_dump($allOptions[0]['value']);die();*/
        
        $this->getCollection()
             ->getSelect()
             ->joinLeft(
     						array('l' => 'customer_entity_int'),
        					'main_table.customer_id = l.entity_id and l.attribute_id ='.$attribute->getId(),
        					array('tipopessoa' => 'value')
        				);
          return parent::_preparePage();
    }

    protected function _prepareColumns()
    {

        $this->addColumn('tipopessoa',
            array(
                'header'=> Mage::helper('sales')->__('Tipo de Pessoa'),
                'width' => '70px',
                'index' => 'tipopessoa',
                'type' => 'options',
                'input' => 'select',
                'options' => $this->_getAttributeOptions('tipopessoa'),
                'filter'    => false,
            ));

        $this->addColumnsOrder('tipopessoa', 'created_at');
        return parent::_prepareColumns();
    }

   protected function _getAttributeOptions($attribute_code)
    {
        $attribute = Mage::getModel('eav/config')->getAttribute('customer', $attribute_code);
        $options = array();
        foreach( $attribute->getSource()->getAllOptions(false) as $option ) {
            $options[$option['value']] = $option['label'];
        }
        return $options;

    }

}
?>