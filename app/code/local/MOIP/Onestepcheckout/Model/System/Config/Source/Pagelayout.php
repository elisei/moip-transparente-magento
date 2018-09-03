<?php
class MOIP_Onestepcheckout_Model_System_Config_Source_Pagelayout
{
    public function toOptionArray()
    {
        return [
                 ['value' => 2, 'label' => Mage::helper('onestepcheckout')->__('2 Colunas')],
                ['value' => 3, 'label' => Mage::helper('onestepcheckout')->__('3 Colunas - revisão do pedido aberta embaixo')],
                ['value' => 4, 'label' => Mage::helper('onestepcheckout')->__('3 Colunas - revisão do pedido na lateral')],
                ['value' => 5, 'label' => Mage::helper('onestepcheckout')->__('3 Colunas - revisão do pedido fechada')],
            ];
    }
}
