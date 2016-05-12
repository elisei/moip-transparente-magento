<?php 
class MOIP_Onestepcheckout_Model_System_Config_Source_Disablefield 
{
    public function toOptionArray()
    {
        return array(
            array('value'=>0, 'label'=>Mage::helper('adminhtml')->__('Desabilitado')),
            array('value'=>1, 'label'=>Mage::helper('adminhtml')->__('Habilitado')),
        );
    }

}
