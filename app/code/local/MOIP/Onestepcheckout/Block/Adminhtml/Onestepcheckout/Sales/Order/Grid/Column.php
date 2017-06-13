<?php
class MOIP_Onestepcheckout_Block_Adminhtml_Onestepcheckout_Sales_Order_Grid_Column extends Mage_Adminhtml_Block_Sales_Order_Grid
{
	 
   protected function _preparePage()
    {

        

        $attributeId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('customer', 'tipopessoa');
        $this->getCollection()
             ->getSelect()->join(
             						array('l' => 'customer_entity_int'),
                					'main_table.customer_id = l.entity_id and l.attribute_id ='.$attributeId,
                					array('pj' => "value")
                				);
      

        return parent::_preparePage();
    }

    protected function _prepareColumns()
    {


        
        $this->addColumn('tipopessoa',
            array(
                'header'=> Mage::helper('sales')->__('Tipo de Pessoa'),
                'width' => '70px',
                'index' => 'pj',
                'filter'    => false,
               	'type'      => 'options',
			    'options'   => array(
			        1 => 'Pessoa Física',
			        0 => 'Pessoa Jurídica'
			    ),
			   
            ));

        $this->addColumnsOrder('tipopessoa', 'shipping_name');
        return parent::_prepareColumns();
    }

   

}
?>