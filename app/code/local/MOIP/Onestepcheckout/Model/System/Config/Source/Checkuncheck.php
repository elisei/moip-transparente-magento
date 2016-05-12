<?php

class MOIP_Onestepcheckout_Model_System_Config_Source_Checkuncheck
{
    public function toOptionArray()
    {
        return array(
            array('value'=>0, 'label'=>Mage::helper('onestepcheckout')->__('Não selecionado')),
            array('value'=>1, 'label'=>Mage::helper('onestepcheckout')->__('Selecionado')),
        );
    }

}
